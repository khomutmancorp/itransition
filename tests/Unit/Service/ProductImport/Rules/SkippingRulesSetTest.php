<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ProductImport\Rules;

use App\DTO\ProductImportDTO;
use App\Service\ProductImport\SkippingRulesSet;
use PHPUnit\Framework\TestCase;

class SkippingRulesSetTest extends TestCase
{
    private SkippingRulesSet $skippingRules;

    protected function setUp(): void
    {
        $this->skippingRules = new SkippingRulesSet();
    }

    public function testShouldSkipReturnsFalse(): void
    {
        $dto = new ProductImportDTO(
            name: 'Test Product',
            description: 'Test Description',
            code: 'TEST001',
            stockLevel: 10,
            price: 19.99,
            addedAt: new \DateTime(),
            discontinuedAt: null
        );

        $this->assertFalse($this->skippingRules->shouldSkip($dto, 1));
        $this->assertFalse($this->skippingRules->shouldSkip($dto, 100));
    }

    public function testWithDiscontinuedProduct(): void
    {
        $dto = new ProductImportDTO(
            name: 'Discontinued Product',
            description: 'Old Product',
            code: 'OLD001',
            stockLevel: 0,
            price: null,
            addedAt: new \DateTime(),
            discontinuedAt: new \DateTime()
        );

        // Even discontinued products should not be skipped with default rules
        $this->assertFalse($this->skippingRules->shouldSkip($dto, 1));
    }

    public function testWithInvalidData(): void
    {
        $dto = new ProductImportDTO(
            name: '',
            description: '',
            code: '',
            stockLevel: null,
            price: null,
            addedAt: new \DateTime(),
            discontinuedAt: null
        );

        // Even invalid data should not be skipped by default rules
        // (validation errors are handled elsewhere)
        $this->assertFalse($this->skippingRules->shouldSkip($dto, 1));
    }
}