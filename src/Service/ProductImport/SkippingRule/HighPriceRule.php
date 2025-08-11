<?php

namespace App\Service\ProductImport\SkippingRule;

use App\DTO\ProductImportDTO;

class HighPriceRule implements SkippingRuleInterface
{
    public function shouldSkip(ProductImportDTO $product): bool
    {
        return $product->price !== null && $product->price > 1000;
    }
}