<?php

declare(strict_types=1);

namespace App\Data;

use Brick\Money\Money;

/**
 * An amount in minor units as events carry it, formatted for mails ("USD 52.20").
 */
final readonly class Amount
{
    public function __construct(
        public int $minor,
        public string $currency,
    ) {}

    public function formatted(): string
    {
        $money = Money::ofMinor($this->minor, $this->currency);

        return $money->getCurrency()->getCurrencyCode().' '.$money->getAmount();
    }
}
