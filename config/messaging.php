<?php

declare(strict_types=1);

use App\Messaging\Handlers\CustomerRegisteredHandler;
use App\Messaging\Handlers\OrderPlacedHandler;
use App\Messaging\Handlers\OrderShippedHandler;
use App\Messaging\Handlers\RefundedHandler;

return [
    'source' => env('SERVICE_NAME', 'notifications'),

    'consumer_group' => env('KAFKA_CONSUMER_GROUP_ID', env('SERVICE_NAME', 'notifications')),

    // Event type => handler. Every type listed here needs its topic in deploy/values.yaml → consumers.
    'handlers' => [
        'identity.customer-registered' => CustomerRegisteredHandler::class,
        'orders.order-placed' => OrderPlacedHandler::class,
        'orders.order-shipped' => OrderShippedHandler::class,
        'payments.refunded' => RefundedHandler::class,
    ],

    'max_attempts' => 3,

    'relay_batch_size' => 100,
];
