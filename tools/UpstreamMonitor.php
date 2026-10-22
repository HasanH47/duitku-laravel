<?php

namespace Duitku\Laravel\Maintenance;

use DOMDocument;
use DOMElement;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class UpstreamMonitor
{
    public const SOURCES = [
        'overview' => ['label' => 'Payment gateway overview', 'url' => 'https://docs.duitku.com/payment-gateway/overview/', 'kind' => 'html'],
        'api' => ['label' => 'Payment API V2', 'url' => 'https://docs.duitku.com/api/id/', 'kind' => 'html'],
        'pop' => ['label' => 'Duitku POP', 'url' => 'https://docs.duitku.com/pop/id/', 'kind' => 'html'],
        'disbursement' => ['label' => 'Disbursement', 'url' => 'https://docs.duitku.com/disbursement/id/', 'kind' => 'html'],
        'cards' => ['label' => 'Direct credit card', 'url' => 'https://docs.duitku.com/direct-credit-card/id/', 'kind' => 'html'],
        'snap' => ['label' => 'SNAP', 'url' => 'https://docs.duitku.com/snap-api/id/', 'kind' => 'html'],
        'php-sdk' => ['label' => 'Official PHP SDK', 'url' => 'https://api.github.com/repos/duitkupg/duitku-php/commits?per_page=1', 'kind' => 'github'],
    ];

    private const MAX_BYTES = 2_000_000;

    private ClientInterface $client;

    private ?string $currentSource = null;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client;
    }

    public function currentSource(): ?string
    {
        return $this->currentSource;
    }

    /** @return array{schema_version: int, sources: array<string, array<string, string>>} */
    public function scan(): array
    {
        $sources = [];

        foreach (self::SOURCES as $id => $source) {
            $this->currentSource = $id;
            $headers = ['User-Agent' => 'Duitku-Laravel-Upstream-Monitor (+https://github.com/HasanH47/duitku-laravel)'];

            // Send the workflow token only to GitHub, never to provider documentation hosts.
            if ($source['kind'] === 'github' && ($token = getenv('GH_TOKEN'))) {
                $headers['Authorization'] = 'Bearer '.$token;
            }

            $response = $this->client->request('GET', $source['url'], [
                'headers' => $headers,
                'timeout' => 20,
                'connect_timeout' => 10,
                'allow_redirects' => false,
                'http_errors' => true,
                'on_headers' => static function (ResponseInterface $response) {
                    if ((int) $response->getHeaderLine('Content-Length') > self::MAX_BYTES) {
                        throw new RuntimeException('Upstream response exceeds the size limit.');
                    }
                },
                'progress' => static function ($total, $downloaded) {
                    if ($downloaded > self::MAX_BYTES) {
                        throw new RuntimeException('Upstream response exceeds the size limit.');
                    }
                },
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Upstream returned an unexpected status for '.$id.'.');
            }

            $body = $response->getBody()->read(self::MAX_BYTES + 1);

            if (strlen($body) > self::MAX_BYTES) {
                throw new RuntimeException('Upstream response exceeds the size limit.');
            }

            if ($source['kind'] === 'github') {
                $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
                $fingerprint = $data[0]['sha'] ?? '';

                if (! is_string($fingerprint) || ! preg_match('/^[a-f0-9]{40}$/', $fingerprint)) {
                    throw new RuntimeException('GitHub did not return an official SDK commit.');
                }
            } else {
                if (! str_contains(strtolower($response->getHeaderLine('Content-Type')), 'text/html')) {
                    throw new RuntimeException('Documentation did not return HTML for '.$id.'.');
                }

                $content = self::normalizeHtml($body);

                if (strlen($content) < 200) {
                    throw new RuntimeException('Documentation content is unexpectedly short for '.$id.'.');
                }

                $fingerprint = hash('sha256', $content);
            }

            $sources[$id] = $source + ['fingerprint' => $fingerprint];
        }

        return ['schema_version' => 1, 'sources' => $sources];
    }

    public static function normalizeHtml(string $html): string
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument;
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($document);
            $candidates = $xpath->query('//main | //article | //*[@id="content-area"] | //div[contains(concat(" ", normalize-space(@class), " "), " content ")]');
            $root = null;

            foreach ($candidates ?: [] as $candidate) {
                if ($candidate instanceof DOMElement && ($root === null || strlen($candidate->textContent) > strlen($root->textContent))) {
                    $root = $candidate;
                }
            }

            if ($root === null) {
                throw new RuntimeException('Documentation content container is missing; review its page layout.');
            }

            $noise = $xpath->query('.//script | .//style | .//nav | .//header | .//footer | .//noscript | .//form', $root);

            foreach (iterator_to_array($noise ?: []) as $node) {
                $node->parentNode?->removeChild($node);
            }

            // Preserve block boundaries when the upstream HTML is minified or reformatted.
            $blocks = $xpath->query('.//h1 | .//h2 | .//h3 | .//h4 | .//p | .//pre | .//li | .//tr | .//th | .//td | .//br', $root);

            foreach (iterator_to_array($blocks ?: []) as $block) {
                $block->parentNode?->insertBefore($document->createTextNode("\n"), $block);
                $block->parentNode?->insertBefore($document->createTextNode("\n"), $block->nextSibling);
            }

            $links = [];

            foreach ($xpath->query('.//a[@href]', $root) ?: [] as $link) {
                if ($link instanceof DOMElement) {
                    $href = trim($link->getAttribute('href'));

                    // Cloudflare rotates these email-obfuscation fragments on every response.
                    $emailProtection = parse_url($href, PHP_URL_PATH) === '/cdn-cgi/l/email-protection';

                    if ($href !== '' && ! str_starts_with($href, '#') && ! $emailProtection) {
                        $links[] = $href;
                    }
                }
            }

            $links = array_unique($links);
            sort($links, SORT_STRING);

            return trim(preg_replace('/\s+/u', ' ', $root->textContent) ?? '')."\n".implode("\n", $links);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /** @return array{changed: bool, changes: list<array<string, string>>} */
    public static function compare(array $baseline, array $current): array
    {
        if (($baseline['schema_version'] ?? null) !== 1 || ! is_array($baseline['sources'] ?? null)) {
            throw new RuntimeException('Upstream baseline schema is missing or unsupported.');
        }

        $changes = [];

        foreach ($current['sources'] as $id => $source) {
            $previous = $baseline['sources'][$id] ?? [];

            if (($previous['fingerprint'] ?? null) !== $source['fingerprint'] || ($previous['url'] ?? null) !== $source['url']) {
                $changes[] = $source + ['id' => $id, 'previous' => $previous['fingerprint'] ?? 'not recorded'];
            }
        }

        return ['changed' => $changes !== [], 'changes' => $changes];
    }

    public static function report(array $result): string
    {
        $lines = ['<!-- duitku-upstream-watch -->', '# Duitku upstream changes', '',
            $result['changed'] ? 'The following sources differ from the reviewed baseline.' : 'All monitored sources match the reviewed baseline.', ''];

        foreach ($result['changes'] as $change) {
            // Labels and URLs come from our source list; upstream HTML is never published in issues.
            $lines[] = '- ['.$change['label'].']('.$change['url'].')';
            $lines[] = '  - Previous: `'.$change['previous'].'`';
            $lines[] = '  - Current: `'.$change['fingerprint'].'`';

            if ($change['kind'] === 'github') {
                $lines[] = '  - [Official SDK commit](https://github.com/duitkupg/duitku-php/commit/'.$change['fingerprint'].')';
            }
        }

        $lines = array_merge($lines, ['', 'Review signature formulas, fields, endpoints, payment channels, and changelogs. Compare relevant SDK behavior, add regression tests, and verify affected sandbox flows before changing payment code.', '',
            'After review, refresh the baseline in a pull request with `php tools/monitor-upstream.php --refresh-baseline`. Close the tracking issue once the differences are addressed or explicitly accepted.', '',
            'A matching snapshot is a change signal, not proof of runtime API compatibility. Failed downloads or unexpected page layouts fail the monitoring job.']);

        return implode("\n", $lines)."\n";
    }
}
