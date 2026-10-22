<?php

use Symfony\Component\Process\Process;

it('creates one tracking issue or updates the existing one while preserving report paths', function (bool $changed, string $number, ?string $operation) {
    $directory = sys_get_temp_dir().'/duitku-notify-'.bin2hex(random_bytes(6));
    mkdir($directory.'/report dir', 0700, true);
    file_put_contents($directory.'/report dir/report.json', json_encode(['changed' => $changed]));
    file_put_contents($directory.'/report dir/report.md', '<!-- duitku-upstream-watch -->');
    file_put_contents($directory.'/gh', <<<'PHP'
#!/usr/bin/env php
<?php
file_put_contents(getenv('GH_CALLS'), json_encode(array_slice($argv, 1)).PHP_EOL, FILE_APPEND);
if (($argv[2] ?? '') === 'list') { echo getenv('MOCK_ISSUE_NUMBER'); }
PHP);
    chmod($directory.'/gh', 0700);

    try {
        $process = new Process(['bash', __DIR__.'/../../tools/notify-upstream.sh', $directory.'/report dir'], env: [
            'PATH' => $directory.PATH_SEPARATOR.getenv('PATH'),
            'GITHUB_REPOSITORY' => 'example/testing',
            'GH_CALLS' => $directory.'/calls.jsonl',
            'MOCK_ISSUE_NUMBER' => $number,
        ]);
        $process->mustRun();
        $calls = file_exists($directory.'/calls.jsonl')
            ? array_map(fn ($line) => json_decode($line, true), file($directory.'/calls.jsonl', FILE_IGNORE_NEW_LINES))
            : [];

        if ($operation === null) {
            expect($calls)->toBe([]);
        } else {
            expect($calls)->toHaveCount(2)
                ->and($calls[1][1])->toBe($operation)
                ->and($calls[1])->toContain($directory.'/report dir/report.md');

            if ($operation === 'edit') {
                expect($calls[1][2])->toBe('42');
            }
        }
    } finally {
        foreach (glob($directory.'/report dir/*') as $path) {
            unlink($path);
        }
        rmdir($directory.'/report dir');
        foreach (glob($directory.'/*') as $path) {
            unlink($path);
        }
        rmdir($directory);
    }
})->with([[false, '', null], [true, '', 'create'], [true, '42', 'edit']]);
