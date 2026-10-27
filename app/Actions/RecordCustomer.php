<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Customer;

/**
 * Upserts a customer's contact data. Identity has no email-change event, so the latest event wins; a missing name
 * (events other than customer-registered) keeps the stored one.
 */
final readonly class RecordCustomer
{
    public function handle(string $customerId, string $email, ?string $name = null): void
    {
        $values = ['customer_id' => $customerId, 'email' => $email];
        $update = ['email'];
        if ($name !== null) {
            $values['name'] = $name;
            $update[] = 'name';
        }

        Customer::query()->upsert([$values], ['customer_id'], $update);
    }
}
