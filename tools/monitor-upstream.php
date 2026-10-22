<?php

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/UpstreamMonitor.php';

use Duitku\Laravel\Maintenance\UpstreamMonitor;

$options = getopt('', ['refresh-baseline', 'output:']) ?: [];
$baselinePath = __DIR__.'/upstream-baseline.json';
$monitor = new UpstreamMonitor;
$stage = 'fetching sources';

try {
    $snapshot = $monitor->scan();
    $stage = 'reading or writing the baseline/report';

    if (array_key_exists('refresh-baseline', $options)) {
        file_put_contents($baselinePath, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
        echo "Baseline refreshed. Review and commit this file after assessing the upstream differences.\n";
        exit(0);
    }

    $baseline = json_decode(file_get_contents($baselinePath), true, flags: JSON_THROW_ON_ERROR);
    $result = UpstreamMonitor::compare($baseline, $snapshot);
    $directory = $options['output'] ?? __DIR__.'/../build/upstream';

    if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        throw new RuntimeException('Cannot create the upstream report directory.');
    }

    file_put_contents($directory.'/report.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    file_put_contents($directory.'/snapshot.json', json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    file_put_contents($directory.'/report.md', UpstreamMonitor::report($result));
    echo $result['changed'] ? "Upstream changes detected. Review the generated report.\n" : "Upstream matches the reviewed baseline.\n";
} catch (Throwable $error) {
    // Exception messages from HTTP libraries can include URLs or request headers.
    $source = $stage === 'fetching sources' ? ' for '.$monitor->currentSource() : '';
    fwrite(STDERR, 'Upstream check failed'.$source.' ('.get_class($error).'). Check connectivity, the source page layout, and the baseline/report files. The baseline was not updated.'.PHP_EOL);
    exit(1);
}
