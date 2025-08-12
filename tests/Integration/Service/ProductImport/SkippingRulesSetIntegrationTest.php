<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\ProductImport;

use App\DTO\ProductImportDTO;

use App\Service\ProductImport\SkippingRule\HighPriceRule;
use App\Service\ProductImport\SkippingRule\LowStockLowPriceRule;
use App\Service\ProductImport\SkippingRulesSet;
use PHPUnit\Framework\TestCase;

final class SkippingRulesSetIntegrationTest extends TestCase
{
    public function testShouldSkipWhenLowStockAndLowPrice(): void
    {
        $product = new ProductImportDTO(
            name: 'Cheap Item',
            description: 'Low price and low stock',
            code: 'C001',
            stockLevel: 5,
            price: 3.0
        );

        $rulesSet = new SkippingRulesSet([
            new LowStockLowPriceRule(),
            new HighPriceRule(),
        ]);

        $this->assertTrue($rulesSet->shouldSkip($product));
    }

    public function testShouldSkipWhenHighPrice(): void
    {
        $product = new ProductImportDTO(
            name: 'Expensive Item',
            description: 'Very expensive',
            code: 'E001',
            stockLevel: 20,
            price: 1500.0
        );

        $rulesSet = new SkippingRulesSet([
            new LowStockLowPriceRule(),
            new HighPriceRule(),
        ]);

        $this->assertTrue($rulesSet->shouldSkip($product));
    }

    public function testShouldNotSkipWhenNormal(): void
    {
        $product = new ProductImportDTO(
            name: 'Normal Item',
            description: 'Normal price and stock',
            code: 'N001',
            stockLevel: 20,
            price: 20.0
        );

        $rulesSet = new SkippingRulesSet([
            new LowStockLowPriceRule(),
            new HighPriceRule(),
        ]);

        $this->assertFalse($rulesSet->shouldSkip($product));
    }
}