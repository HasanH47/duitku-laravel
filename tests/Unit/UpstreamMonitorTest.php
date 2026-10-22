<?php

use Duitku\Laravel\Maintenance\UpstreamMonitor;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

require_once __DIR__.'/../../tools/UpstreamMonitor.php';

it('ignores navigation script and presentation changes while retaining documentation meaning', function () {
    $first = '<html><nav>Today</nav><main><h1>Duitku</h1><p>Use HMAC &amp; rupiah.</p><script>random()</script><footer>2026</footer></main></html>';
    $second = '<html><nav>Tomorrow</nav><main class="new-style"><h1>Duitku</h1> <p>Use HMAC &amp; rupiah.</p><script>other()</script><footer>2027</footer></main></html>';

    expect(UpstreamMonitor::normalizeHtml($first))->toBe(UpstreamMonitor::normalizeHtml($second));
});

it('preserves Unicode code examples and relevant link changes', function () {
    $html = '<div class="content"><h1>Pembayaran</h1><p>Rp10.000 — berhasil</p><pre>hash_hmac("sha256", $message, $key)</pre><a href="https://sandbox.duitku.com/old">API</a></div>';

    expect(UpstreamMonitor::normalizeHtml($html))->toContain('berhasil', 'hash_hmac', 'https://sandbox.duitku.com/old')
        ->and(UpstreamMonitor::normalizeHtml($html))->not->toBe(UpstreamMonitor::normalizeHtml(str_replace('/old', '/new', $html)));
});

it('ignores rotating Cloudflare email links while retaining payment endpoint changes', function () {
    $first = '<main><p>Contact <a href="/cdn-cgi/l/email-protection#abcd">[email protected]</a></p><a href="https://sandbox.duitku.com/api/v2/inquiry">Create invoice</a></main>';
    $second = str_replace('#abcd', '#ef0123', $first);

    expect(UpstreamMonitor::normalizeHtml($first))->toBe(UpstreamMonitor::normalizeHtml($second))
        ->and(UpstreamMonitor::normalizeHtml($first))->not->toBe(UpstreamMonitor::normalizeHtml(str_replace('/v2/', '/v3/', $second)));
});

it('fails when a challenge or changed page layout hides the documentation', function () {
    expect(fn () => UpstreamMonitor::normalizeHtml('<html><body>Just a moment...</body></html>'))->toThrow(RuntimeException::class);
});

it('detects changed sources without publishing upstream page text', function () {
    $current = ['schema_version' => 1, 'sources' => ['api' => UpstreamMonitor::SOURCES['api'] + ['fingerprint' => 'new-hash']]];
    $baseline = ['schema_version' => 1, 'sources' => ['api' => UpstreamMonitor::SOURCES['api'] + ['fingerprint' => 'old-hash']]];
    $result = UpstreamMonitor::compare($baseline, $current);

    expect($result['changed'])->toBeTrue()->and($result['changes'])->toHaveCount(1)
        ->and(UpstreamMonitor::report($result))->toContain('old-hash', 'new-hash', '<!-- duitku-upstream-watch -->')
        ->and(UpstreamMonitor::compare($current, $current)['changed'])->toBeFalse();
});

it('refuses a baseline with an unsupported schema', function () {
    expect(fn () => UpstreamMonitor::compare([], ['sources' => []]))->toThrow(RuntimeException::class);
});

it('uses bounded fetches and sends a GitHub token only to GitHub', function () {
    $responses = [];

    foreach (UpstreamMonitor::SOURCES as $source) {
        $responses[] = $source['kind'] === 'github'
            ? new Response(200, ['Content-Type' => 'application/json'], json_encode([['sha' => str_repeat('a', 40)]]))
            : new Response(200, ['Content-Type' => 'text/html'], '<main>'.str_repeat('Synthetic payment documentation. ', 20).'</main>');
    }

    $history = [];
    $handler = HandlerStack::create(new MockHandler($responses));
    $handler->push(Middleware::history($history));
    $previous = getenv('GH_TOKEN');
    putenv('GH_TOKEN=synthetic-github-token');

    try {
        $snapshot = (new UpstreamMonitor(new Client(['handler' => $handler])))->scan();

        expect($snapshot['sources'])->toHaveCount(7);

        foreach ($history as $request) {
            expect($request['options']['timeout'])->toBe(20)
                ->and($request['options']['allow_redirects'])->toBeFalse();
            $github = $request['request']->getUri()->getHost() === 'api.github.com';
            expect($request['request']->hasHeader('Authorization'))->toBe($github);
        }
    } finally {
        putenv($previous === false ? 'GH_TOKEN' : 'GH_TOKEN='.$previous);
    }
});

it('does not treat HTTP failures or redirects as an unchanged source', function (int $status, string $exception) {
    $handler = HandlerStack::create(new MockHandler([new Response($status)]));

    expect(fn () => (new UpstreamMonitor(new Client(['handler' => $handler])))->scan())->toThrow($exception);
})->with([[301, RuntimeException::class], [403, ClientException::class], [500, ServerException::class]]);
