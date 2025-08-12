<?php

declare(strict_types=1);

namespace App\Service\ProductImport\SkippingRule;

use App\DTO\ProductImportDTO;

interface SkippingRuleInterface
{
    /**
     * Determine if the item should be skipped during import
     *
     * @param ProductImportDTO $dto The product data to evaluate
     * @return bool True if the item should be skipped, false otherwise
     */
    public function shouldSkip(ProductImportDTO $product): bool;
}