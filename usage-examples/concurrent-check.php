<?php

use Duitku\Laravel\Facades\Duitku;

/**
 * Example: Concurrent Status Checks using Laravel Http::pool
 * This is significantly faster for bulk checks.
 */
$orderIds = ['ORD-001', 'ORD-002', 'ORD-003', 'ORD-004', 'ORD-005'];

$startTime = microtime(true);

// This sends all requests in parallel!
$statuses = Duitku::checkStatuses($orderIds);

$endTime = microtime(true);
$duration = $endTime - $startTime;

foreach ($statuses as $status) {
    if ($status) {
        echo "Order: {$status->merchantOrderId} - Status: {$status->statusCode}\n";
    } else {
        echo "Failed to check status for an order.\n";
    }
}

echo 'Total Time (Concurrent): '.number_format($duration, 4)." seconds\n";
