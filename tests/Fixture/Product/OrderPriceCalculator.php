<?php

declare(strict_types=1);

namespace Test\Ecotone\Laravel\Fixture\Product;

use Money\Money;

final class OrderPriceCalculator
{
    public function calculateFor(array $productIds): Money
    {
        return Money::GBP('100.00');
    }
}
