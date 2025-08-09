<?php

namespace App\Service\ProductImport\SkippingRule;

use App\DTO\ProductImportDTO;

class LowStockLowPriceRule implements SkippingRuleInterface
{
    final public function shouldSkip(ProductImportDTO $product): bool
    {
        return ($product->price !== null && $product->price < 5)
            && ($product->stockLevel !== null && $product->stockLevel < 10);
    }
}