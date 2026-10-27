<?php

declare(strict_types=1);

namespace App\Data;

final readonly class OrderLine
{
    public function __construct(
        public string $sku,
        public ?string $productName,
        public string $variantName,
        public int $quantity,
        public Amount $unitPrice,
        public Amount $lineTotal,
    ) {}

    public function label(): string
    {
        return $this->productName === null ? $this->variantName : "{$this->productName} ({$this->variantName})";
    }
}
