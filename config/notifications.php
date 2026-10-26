<?php

declare(strict_types=1);

return [
    // Links in emails point at the storefront (the only browser-facing app for customers).
    'storefront_url' => env('STOREFRONT_URL', 'http://localhost:3000'),
];
