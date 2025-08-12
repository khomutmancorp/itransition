<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ProductImport\Statistics;

use App\Service\ProductImport\Statistics\ImportStatistics;
use PHPUnit\Framework\TestCase;

final class ImportStatisticsTest extends TestCase
{
    private ImportStatistics $statistics;

    protected function setUp(): void
    {
        $this->statistics = new ImportStatistics();
    }

    public function testInitialState(): void
    {
        $result = $this->statistics->toResult();

        $this->assertSame(0, $result->processed);
        $this->assertSame(0, $result->created);
        $this->assertSame(0, $result->updated);
        $this->assertSame(0, $result->getSuccess());
        $this->assertSame(0, $result->skipped);
        $this->assertSame([], $result->errors);
        $this->assertFalse($this->statistics->hasErrors());
    }

    public function testIncrementCounters(): void
    {
        $this->statistics->incrementProcessed();
        $this->statistics->incrementProcessed();
        $this->statistics->incrementCreated();
        $this->statistics->incrementUpdated();
        $this->statistics->incrementSkipped();

        $result = $this->statistics->toResult();

        $this->assertSame(2, $result->processed);
        $this->assertSame(1, $result->created);
        $this->assertSame(1, $result->updated);
        $this->assertSame(2, $result->getSuccess());
        $this->assertSame(1, $result->skipped);
    }

    public function testAddSingleError(): void
    {
        $errorMessage = 'Test error message';
        
        $this->statistics->addError($errorMessage);

        $this->assertTrue($this->statistics->hasErrors());
        $this->assertSame([$errorMessage], $this->statistics->getErrors());

        $result = $this->statistics->toResult();
        $this->assertSame([$errorMessage], $result->errors);
    }

    public function testAddMultipleErrors(): void
    {
        $error1 = 'First error message';
        $error2 = 'Second error message';
        $error3 = 'Third error message';

        $this->statistics->addError($error1);
        $this->statistics->addError($error2);
        $this->statistics->addError($error3);

        $expectedErrors = [$error1, $error2, $error3];

        $this->assertTrue($this->statistics->hasErrors());
        $this->assertSame($expectedErrors, $this->statistics->getErrors());

        $result = $this->statistics->toResult();
        $this->assertSame($expectedErrors, $result->errors);
    }

    public function testAddFormattedErrorRecord(): void
    {
        // Test the error format used in ProductImportService
        $record = ['code' => 'PROD001', 'name' => 'Test Product'];
        $errorMessage = 'Price must be numeric';
        $index = 2;
        
        $formattedError = sprintf(
            "Record %s can't be added: %s. Line: %d",
            json_encode($record, JSON_THROW_ON_ERROR),
            $errorMessage,
            $index
        );

        $this->statistics->addError($formattedError);

        $expectedError = 'Record {"code":"PROD001","name":"Test Product"} can\'t be added: Price must be numeric. Line: 2';
        
        $this->assertTrue($this->statistics->hasErrors());
        $this->assertSame([$expectedError], $this->statistics->getErrors());

        $result = $this->statistics->toResult();
        $this->assertSame([$expectedError], $result->errors);
    }

    public function testCompleteWorkflow(): void
    {
        // Simulate processing 5 records with 2 created, 1 updated, 1 skip, and 1 error
        $this->statistics->incrementProcessed(); // Record 1 - new
        $this->statistics->incrementCreated();

        $this->statistics->incrementProcessed(); // Record 2 - updated
        $this->statistics->incrementUpdated();

        $this->statistics->incrementProcessed(); // Record 3 - skipped
        $this->statistics->incrementSkipped();

        $this->statistics->incrementProcessed(); // Record 4 - error
        $this->statistics->addError('Record {"code":"INVALID"} can\'t be added: Code is required. Line: 4');

        $this->statistics->incrementProcessed(); // Record 5 - new
        $this->statistics->incrementCreated();

        $result = $this->statistics->toResult();

        $this->assertSame(5, $result->processed);
        $this->assertSame(2, $result->created);
        $this->assertSame(1, $result->updated);
        $this->assertSame(3, $result->getSuccess()); // created + updated
        $this->assertSame(1, $result->skipped);
        $this->assertCount(1, $result->errors);
        $this->assertTrue($this->statistics->hasErrors());
    }

    public function testIncrementCreatedAndUpdated(): void
    {
        $this->statistics->incrementCreated();
        $this->statistics->incrementCreated();
        $this->statistics->incrementUpdated();

        $result = $this->statistics->toResult();

        $this->assertSame(2, $result->created);
        $this->assertSame(1, $result->updated);
        $this->assertSame(3, $result->getSuccess());
    }

    public function testGetSuccessCalculatesCorrectly(): void
    {
        $this->statistics->incrementCreated();
        $this->statistics->incrementCreated();
        $this->statistics->incrementCreated();
        $this->statistics->incrementUpdated();
        $this->statistics->incrementUpdated();

        $result = $this->statistics->toResult();

        $this->assertSame(3, $result->created);
        $this->assertSame(2, $result->updated);
        $this->assertSame(5, $result->getSuccess());
    }

    public function testIncrementCreatedWithCustomCount(): void
    {
        $this->statistics->incrementCreated(3);
        $this->statistics->incrementCreated(2);

        $result = $this->statistics->toResult();

        $this->assertSame(5, $result->created);
        $this->assertSame(0, $result->updated);
        $this->assertSame(5, $result->getSuccess());
    }

    public function testIncrementUpdatedWithCustomCount(): void
    {
        $this->statistics->incrementUpdated(4);
        $this->statistics->incrementUpdated();

        $result = $this->statistics->toResult();

        $this->assertSame(0, $result->created);
        $this->assertSame(5, $result->updated);
        $this->assertSame(5, $result->getSuccess());
    }

    public function testMixedIncrementWithDefaultAndCustomCounts(): void
    {
        $this->statistics->incrementCreated(3); // Custom count
        $this->statistics->incrementCreated();  // Default count (1)
        $this->statistics->incrementUpdated(2); // Custom count
        $this->statistics->incrementUpdated();  // Default count (1)

        $result = $this->statistics->toResult();

        $this->assertSame(4, $result->created); // 3 + 1
        $this->assertSame(3, $result->updated); // 2 + 1
        $this->assertSame(7, $result->getSuccess());
    }
}