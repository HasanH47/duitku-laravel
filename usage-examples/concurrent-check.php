<?php

use Duitku\Laravel\Facades\Duitku;

/**
 * Example: Bounded asynchronous status checks
 * Each input produces a value or an explicit error.
 */
$orderIds = ['ORD-001', 'ORD-002', 'ORD-003', 'ORD-004', 'ORD-005'];

$startTime = microtime(true);

// Requests run in bounded chunks using the configured concurrency.
$statuses = Duitku::checkStatuses($orderIds);

$endTime = microtime(true);
$duration = $endTime - $startTime;

foreach ($statuses as $result) {
    if ($result->successful()) {
        $status = $result->get();
        echo "Order: {$status->merchantOrderId} - Status: {$status->statusCode}\n";
    } else {
        echo "Failed to check status for an order.\n";
    }
}

echo 'Total Time (Concurrent): '.number_format($duration, 4)." seconds\n";
