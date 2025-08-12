<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ProductImport\Batch;

use App\DTO\ProductImportDTO;
use App\Service\ProductImport\Batch\BatchProcessor;
use App\Service\ProductImport\BulkWriterInterface;
use App\Service\ProductImport\ProductBulkWriter;
use App\Service\ProductImport\Statistics\ImportStatistics;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BatchProcessorTest extends TestCase
{
    private BatchProcessor $processor;
    private BulkWriterInterface&MockObject $bulkWriter;

    protected function setUp(): void
    {
        $this->bulkWriter = $this->createMock(BulkWriterInterface::class);
        $this->processor = new BatchProcessor($this->bulkWriter, 2); // Small batch size for testing
    }

    public function testInitialStateIsEmpty(): void
    {
        $this->assertTrue($this->processor->isEmpty());
    }

    public function testAddToBatchStoresDTOs(): void
    {
        $dto = new ProductImportDTO('Test Product', 'Test Description', 'P001', 10, 19.99);
        
        $this->processor->addToBatch($dto);
        
        $this->assertFalse($this->processor->isEmpty());
    }

    public function testAddToBatchDoesNotAutoFlushUntilBatchSizeReached(): void
    {
        $dto1 = new ProductImportDTO('Product 1', 'Description 1', 'P001', 10, 19.99);
        
        // Should not call flush yet since batch size is 2
        $this->bulkWriter->expects($this->never())
            ->method('addToBatch');
        $this->bulkWriter->expects($this->never())
            ->method('flushBatch');
        
        $this->processor->addToBatch($dto1);
        
        $this->assertFalse($this->processor->isEmpty());
    }

    public function testAutoFlushWhenBatchSizeReached(): void
    {
        $dto1 = new ProductImportDTO('Product 1', 'Description 1', 'P001', 10, 19.99);
        $dto2 = new ProductImportDTO('Product 2', 'Description 2', 'P002', 20, 29.99);
        
        // Should call addToBatch twice and flushBatch once when batch size (2) is reached
        $this->bulkWriter->expects($this->exactly(2))
            ->method('addToBatch')
            ->willReturnCallback(function($dto) use ($dto1, $dto2) {
                static $callCount = 0;
                $callCount++;
                if ($callCount === 1) {
                    $this->assertEquals($dto1, $dto);
                } elseif ($callCount === 2) {
                    $this->assertEquals($dto2, $dto);
                }
            });
        
        $this->bulkWriter->expects($this->once())
            ->method('flushBatch');
        
        $this->processor->addToBatch($dto1);
        $this->assertFalse($this->processor->isEmpty());
        
        // Adding second item should trigger auto-flush
        $this->processor->addToBatch($dto2);
        $this->assertTrue($this->processor->isEmpty()); // Should be cleared after flush
    }

    public function testManualFlushWithItems(): void
    {
        $dto1 = new ProductImportDTO('Product 1', 'Description 1', 'P001', 10, 19.99);
        $dto2 = new ProductImportDTO('Product 2', 'Description 2', 'P002', 20, 29.99);
        
        $this->processor->addToBatch($dto1);
        
        // Manual flush should process the item
        $this->bulkWriter->expects($this->once())
            ->method('addToBatch')
            ->with($dto1);
        
        $this->bulkWriter->expects($this->once())
            ->method('flushBatch');
        
        $this->processor->flush();
        
        $this->assertTrue($this->processor->isEmpty());
    }

    public function testManualFlushWithEmptyBatch(): void
    {
        // Should not call any methods on empty batch
        $this->bulkWriter->expects($this->never())
            ->method('addToBatch');
        $this->bulkWriter->expects($this->never())
            ->method('flushBatch');
        
        $this->processor->flush();
        
        $this->assertTrue($this->processor->isEmpty());
    }

    public function testFlushProcessesAllItemsInOrder(): void
    {
        $dto1 = new ProductImportDTO('Product 1', 'Description 1', 'P001', 10, 19.99);
        $dto2 = new ProductImportDTO('Product 2', 'Description 2', 'P002', 20, 29.99);
        $dto3 = new ProductImportDTO('Product 3', 'Description 3', 'P003', 30, 39.99);
        
        // Create processor with larger batch size to prevent auto-flush
        $processor = new BatchProcessor($this->bulkWriter, 5);
        
        $processor->addToBatch($dto1);
        $processor->addToBatch($dto2);
        $processor->addToBatch($dto3);
        
        // Should call addToBatch for each item in order
        $this->bulkWriter->expects($this->exactly(3))
            ->method('addToBatch')
            ->willReturnCallback(function($dto) use ($dto1, $dto2, $dto3) {
                static $callCount = 0;
                $callCount++;
                if ($callCount === 1) {
                    $this->assertEquals($dto1, $dto);
                } elseif ($callCount === 2) {
                    $this->assertEquals($dto2, $dto);
                } elseif ($callCount === 3) {
                    $this->assertEquals($dto3, $dto);
                }
            });
        
        $this->bulkWriter->expects($this->once())
            ->method('flushBatch');
        
        $processor->flush();
        
        $this->assertTrue($processor->isEmpty());
    }

    public function testGetCreatedCountWithProductBulkWriter(): void
    {
        $productBulkWriter = $this->createMock(ProductBulkWriter::class);
        $productBulkWriter->expects($this->once())
            ->method('getCreatedCount')
            ->willReturn(5);
        
        $processor = new BatchProcessor($productBulkWriter);
        
        $this->assertSame(5, $processor->getCreatedCount());
    }

    public function testGetUpdatedCountWithProductBulkWriter(): void
    {
        $productBulkWriter = $this->createMock(ProductBulkWriter::class);
        $productBulkWriter->expects($this->once())
            ->method('getUpdatedCount')
            ->willReturn(3);
        
        $processor = new BatchProcessor($productBulkWriter);
        
        $this->assertSame(3, $processor->getUpdatedCount());
    }

    public function testGetCreatedCountWithGenericBulkWriter(): void
    {
        // With generic BulkWriterInterface, should return 0
        $this->assertSame(0, $this->processor->getCreatedCount());
    }

    public function testGetUpdatedCountWithGenericBulkWriter(): void
    {
        // With generic BulkWriterInterface, should return 0
        $this->assertSame(0, $this->processor->getUpdatedCount());
    }

    public function testDefaultBatchSize(): void
    {
        $processor = new BatchProcessor($this->bulkWriter); // No batch size specified
        
        // Add 999 items - should not auto-flush (default batch size is 1000)
        for ($i = 0; $i < 999; $i++) {
            $dto = new ProductImportDTO("Product $i", "Description $i", "P$i", 10, 19.99);
            $processor->addToBatch($dto);
        }
        
        $this->bulkWriter->expects($this->never())
            ->method('flushBatch');
        
        $this->assertFalse($processor->isEmpty());
    }

    public function testDefaultBatchSizeAutoFlush(): void
    {
        $processor = new BatchProcessor($this->bulkWriter); // Default batch size 1000
        
        // Add exactly 1000 items - should auto-flush
        $this->bulkWriter->expects($this->exactly(1000))
            ->method('addToBatch');
        
        $this->bulkWriter->expects($this->once())
            ->method('flushBatch');
        
        for ($i = 0; $i < 1000; $i++) {
            $dto = new ProductImportDTO("Product $i", "Description $i", "P$i", 10, 19.99);
            $processor->addToBatch($dto);
        }
        
        $this->assertTrue($processor->isEmpty());
    }

    public function testMultipleFlushCycles(): void
    {
        // Test multiple cycles of add/flush to ensure state is properly maintained
        $dto1 = new ProductImportDTO('Product 1', 'Description 1', 'P001', 10, 19.99);
        $dto2 = new ProductImportDTO('Product 2', 'Description 2', 'P002', 20, 29.99);
        $dto3 = new ProductImportDTO('Product 3', 'Description 3', 'P003', 30, 39.99);
        
        // First cycle
        $this->processor->addToBatch($dto1);
        $this->processor->flush();
        $this->assertTrue($this->processor->isEmpty());
        
        // Second cycle
        $this->processor->addToBatch($dto2);
        $this->processor->addToBatch($dto3);
        $this->processor->flush();
        $this->assertTrue($this->processor->isEmpty());
    }

    public function testFlushWithStatisticsUpdatesCountsCorrectly(): void
    {
        $productBulkWriter = $this->createMock(ProductBulkWriter::class);
        $statistics = $this->createMock(ImportStatistics::class);
        
        // Mock the counts before and after flush
        $productBulkWriter->expects($this->exactly(2))
            ->method('getCreatedCount')
            ->willReturnOnConsecutiveCalls(0, 3); // 0 before, 3 after
            
        $productBulkWriter->expects($this->exactly(2))
            ->method('getUpdatedCount')
            ->willReturnOnConsecutiveCalls(0, 2); // 0 before, 2 after
            
        $productBulkWriter->expects($this->once())
            ->method('addToBatch');
            
        $productBulkWriter->expects($this->once())
            ->method('flushBatch');
        
        // Expect statistics to be updated with the difference
        $statistics->expects($this->once())
            ->method('incrementCreated')
            ->with(3);
            
        $statistics->expects($this->once())
            ->method('incrementUpdated')
            ->with(2);
        
        $processor = new BatchProcessor($productBulkWriter, 5);
        $dto = new ProductImportDTO('Product 1', 'Description 1', 'P001', 10, 19.99);
        $processor->addToBatch($dto);
        
        $processor->flush($statistics);
    }

    public function testFlushWithoutStatisticsDoesNotUpdateStatistics(): void
    {
        $dto = new ProductImportDTO('Product 1', 'Description 1', 'P001', 10, 19.99);
        
        $this->processor->addToBatch($dto);
        
        $this->bulkWriter->expects($this->once())
            ->method('addToBatch')
            ->with($dto);
        
        $this->bulkWriter->expects($this->once())
            ->method('flushBatch');
        
        // Should not throw any errors when no statistics provided
        $this->processor->flush();
        
        $this->assertTrue($this->processor->isEmpty());
    }

    public function testFlushWithStatisticsButGenericBulkWriter(): void
    {
        $statistics = $this->createMock(ImportStatistics::class);
        $dto = new ProductImportDTO('Product 1', 'Description 1', 'P001', 10, 19.99);
        
        $this->processor->addToBatch($dto);
        
        // With generic BulkWriterInterface, counts will be 0
        $statistics->expects($this->once())
            ->method('incrementCreated')
            ->with(0);
            
        $statistics->expects($this->once())
            ->method('incrementUpdated')
            ->with(0);
        
        $this->processor->flush($statistics);
    }
}