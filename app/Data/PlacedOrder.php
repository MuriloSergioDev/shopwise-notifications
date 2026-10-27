<?php

declare(strict_types=1);

namespace App\Data;

use InvalidArgumentException;
use Shopwise\Contracts\Events\Orders\OrderPlacedV1;

/**
 * What the order confirmation needs from orders.order-placed v1. The generated OrderPlacedV1 leaves the nested
 * lines and shipping address as untyped arrays, so those are checked and typed here.
 */
final readonly class PlacedOrder
{
    /**
     * @param  list<OrderLine>  $lines
     * @param  list<string>  $shippingAddress  printable address lines
     */
    public function __construct(
        public string $orderId,
        public string $number,
        public string $customerId,
        public string $email,
        public string $recipientName,
        public array $lines,
        public Amount $subtotal,
        public Amount $discount,
        public Amount $total,
        public ?string $couponCode,
        public array $shippingAddress,
    ) {}

    public static function fromEvent(OrderPlacedV1 $event): self
    {
        $currency = $event->currency;
        /** @var array<array-key, mixed> $address */
        $address = $event->shippingAddress;

        $lines = [];
        /** @var array<array-key, mixed> $eventLines */
        $eventLines = $event->lines;
        foreach ($eventLines as $line) {
            if (! is_array($line)) {
                throw new InvalidArgumentException('Order line must be an object.');
            }

            /** @var array<string, mixed> $line */
            $lines[] = new OrderLine(
                sku: self::string($line, 'sku'),
                productName: self::nullableString($line, 'productName'),
                variantName: self::string($line, 'variantName'),
                quantity: self::int($line, 'quantity'),
                unitPrice: new Amount(self::int($line, 'unitAmount'), $currency),
                lineTotal: new Amount(self::int($line, 'lineAmount'), $currency),
            );
        }

        return new self(
            orderId: $event->orderId,
            number: $event->number,
            customerId: $event->customerId,
            email: $event->email,
            recipientName: self::string($address, 'name'),
            lines: $lines,
            subtotal: new Amount($event->subtotal, $currency),
            discount: new Amount($event->discount, $currency),
            total: new Amount($event->total, $currency),
            couponCode: $event->couponCode,
            shippingAddress: array_values(array_filter([
                self::string($address, 'name'),
                self::string($address, 'line1'),
                self::nullableString($address, 'line2'),
                mb_trim(self::string($address, 'postalCode').' '.self::string($address, 'city')),
                self::nullableString($address, 'region'),
                self::string($address, 'country'),
            ], fn (?string $part): bool => $part !== null && $part !== '')),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function string(array $data, string $field): string
    {
        $value = $data[$field] ?? null;
        if (! is_string($value)) {
            throw new InvalidArgumentException("Field '{$field}' must be a string.");
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function nullableString(array $data, string $field): ?string
    {
        return ($data[$field] ?? null) === null ? null : self::string($data, $field);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function int(array $data, string $field): int
    {
        $value = $data[$field] ?? null;
        if (! is_int($value)) {
            throw new InvalidArgumentException("Field '{$field}' must be an integer.");
        }

        return $value;
    }
}
