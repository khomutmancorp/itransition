<?php

declare(strict_types=1);

namespace App\Service\ProductImport;

use App\DTO\ProductImportDTO;
use App\Service\ProductImport\Interface\SkippingRulesSetInterface;
use App\Service\ProductImport\SkippingRule\SkippingRuleInterface;

readonly class SkippingRulesSet implements SkippingRulesSetInterface
{
    /**
     * @param iterable<SkippingRuleInterface> $rules
     */
    public function __construct(private iterable $rules)
    {
    }

    public function shouldSkip(ProductImportDTO $product): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->shouldSkip($product)) {
                return true;
            }
        }
        return false;
    }
}