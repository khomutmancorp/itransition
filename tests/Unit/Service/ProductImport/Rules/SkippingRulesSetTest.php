<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ProductImport\Rules;

use App\DTO\ProductImportDTO;
use App\Service\ProductImport\SkippingRulesSet;
use App\Service\ProductImport\SkippingRule\SkippingRuleInterface;
use PHPUnit\Framework\TestCase;

final class SkippingRulesSetTest extends TestCase
{
    public function testShouldSkipReturnsFalseWithNoRules(): void
    {
        $skippingRules = new SkippingRulesSet([]);
        $dto = new ProductImportDTO(
            name: 'Test Product',
            description: 'Test Description',
            code: 'TEST001',
            stockLevel: 10,
            price: 19.99,
            discontinuedAt: null
        );

        $this->assertFalse($skippingRules->shouldSkip($dto));
    }

    public function testShouldSkipReturnsTrueWhenRuleMatches(): void
    {
        $rule = $this->createMock(SkippingRuleInterface::class);
        $rule->method('shouldSkip')->willReturn(true);

        $skippingRules = new SkippingRulesSet([$rule]);
        $dto = new ProductImportDTO(
            name: 'Skip Product',
            description: 'Should be skipped',
            code: 'SKIP001',
            stockLevel: 1,
            price: 1.0,
            discontinuedAt: null
        );

        $this->assertTrue($skippingRules->shouldSkip($dto));
    }

    public function testShouldSkipReturnsFalseWhenNoRuleMatches(): void
    {
        $rule = $this->createMock(SkippingRuleInterface::class);
        $rule->method('shouldSkip')->willReturn(false);

        $skippingRules = new SkippingRulesSet([$rule]);
        $dto = new ProductImportDTO(
            name: 'Valid Product',
            description: 'Should not be skipped',
            code: 'VALID001',
            stockLevel: 100,
            price: 100.0,
            discontinuedAt: null
        );

        $this->assertFalse($skippingRules->shouldSkip($dto));
    }

    public function testShouldSkipReturnsTrueIfAnyRuleMatches(): void
    {
        $rule1 = $this->createMock(SkippingRuleInterface::class);
        $rule1->method('shouldSkip')->willReturn(false);

        $rule2 = $this->createMock(SkippingRuleInterface::class);
        $rule2->method('shouldSkip')->willReturn(true);

        $skippingRules = new SkippingRulesSet([$rule1, $rule2]);
        $dto = new ProductImportDTO(
            name: 'Mixed Product',
            description: 'Should be skipped by second rule',
            code: 'MIXED001',
            stockLevel: 5,
            price: 2.0,
            discontinuedAt: null
        );

        $this->assertTrue($skippingRules->shouldSkip($dto));
    }
}