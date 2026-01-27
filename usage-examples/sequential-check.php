<?php

use Duitku\Laravel\Facades\Duitku;

/**
 * Example: Sequential Status Checks (Traditional Loop)
 * This is slower because it waits for each request to finish before starting the next.
 */
$orderIds = ['ORD-001', 'ORD-002', 'ORD-003', 'ORD-004', 'ORD-005'];

$startTime = microtime(true);

foreach ($orderIds as $orderId) {
    // This blocks!
    $status = Duitku::checkStatus($orderId);
    echo "Order: {$status->merchantOrderId} - Status: {$status->statusCode}\n";
}

$endTime = microtime(true);
$duration = $endTime - $startTime;

echo 'Total Time (Sequential): '.number_format($duration, 4)." seconds\n";
