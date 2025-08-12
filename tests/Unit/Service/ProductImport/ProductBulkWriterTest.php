<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ProductImport;

use App\DTO\ProductImportDTO;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\ProductImport\ProductBulkWriter;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProductBulkWriterTest extends TestCase
{
    private ProductBulkWriter $writer;
    private EntityManagerInterface&MockObject $entityManager;
    private ProductRepository&MockObject $productRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        
        $this->writer = new ProductBulkWriter(
            $this->entityManager,
            $this->productRepository,
            2 // small batch size for testing
        );
    }

    public function testAddToBatchStoresDTOs(): void
    {
        $dto = new ProductImportDTO('Test Product', 'Test Description', 'P001', 10, 19.99);
        
        $this->writer->addToBatch($dto);
        
        $this->assertSame(1, $this->writer->getCurrentBatchCount());
    }

    public function testFlushBatchWithNewProducts(): void
    {
        // Mock repository to return no existing products
        $this->productRepository->expects($this->once())
            ->method('findByCodes')
            ->with(['P001', 'P002'])
            ->willReturn([]);
        
        // Expect persist to be called twice for new products
        $this->entityManager->expects($this->exactly(2))
            ->method('persist');
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->entityManager->expects($this->once())
            ->method('clear');

        $dto1 = new ProductImportDTO('Product 1', 'Description 1', 'P001', 10, 19.99);
        $dto2 = new ProductImportDTO('Product 2', 'Description 2', 'P002', 20, 29.99);

        $this->writer->addToBatch($dto1);
        $this->writer->addToBatch($dto2);

        $this->writer->flushBatch();
        $this->assertSame(2, $this->writer->getCreatedCount());
        $this->assertSame(0, $this->writer->getUpdatedCount());
        $this->assertSame(0, $this->writer->getCurrentBatchCount());
    }

    public function testFlushBatchWithExistingProducts(): void
    {
        // Create existing product
        $existingProduct = new Product();
        $existingProduct->setCode('P001')
            ->setName('Old Product 1')
            ->setDescription('Old Description 1')
            ->setStockLevel(10)
            ->setPrice('19.99');
        
        // Mock repository to return existing product for P001
        $this->productRepository->expects($this->once())
            ->method('findByCodes')
            ->with(['P001', 'P002'])
            ->willReturn(['P001' => $existingProduct]);
        
        // Expect persist to be called once for new product P002
        $this->entityManager->expects($this->once())
            ->method('persist');
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        $dto1 = new ProductImportDTO('Updated Product 1', 'Updated Description 1', 'P001', 15, 25.99);
        $dto2 = new ProductImportDTO('Product 2', 'Description 2', 'P002', 20, 29.99);

        $this->writer->addToBatch($dto1);
        $this->writer->addToBatch($dto2);
        
        $this->writer->flushBatch();

        $this->assertSame(1, $this->writer->getCreatedCount());
        $this->assertSame(1, $this->writer->getUpdatedCount());
        
        // Verify existing product was updated
        $this->assertSame('Updated Product 1', $existingProduct->getName());
        $this->assertSame('Updated Description 1', $existingProduct->getDescription());
        $this->assertSame(15, $existingProduct->getStockLevel());
        $this->assertSame('25.99', $existingProduct->getPrice());
    }

    public function testFlushBatchWithMixedProducts(): void
    {
        $dto1 = new ProductImportDTO('Updated Product', 'Updated Description', 'P001', 15, 25.99);
        $dto2 = new ProductImportDTO('New Product', 'New Description', 'P002', 20, 29.99);
        $dto3 = new ProductImportDTO('Another Updated', 'Another Description', 'P003', 5, 9.99);
        
        // Create existing products
        $existingProduct1 = new Product();
        $existingProduct1
            ->setCode('P001')
            ->setName('Old Product 1')
            ->setDescription('Old Description 1');
        
        $existingProduct3 = new Product();
        $existingProduct3
            ->setCode('P003')
            ->setName('Old Product 3')
            ->setDescription('Old Description 3');
        
        // Mock repository to return existing products for P001 and P003
        $this->productRepository->expects($this->exactly(2))
            ->method('findByCodes')
            ->willReturnMap([
                [['P001', 'P002'], ['P001' => $existingProduct1]],
                [['P003'], ['P003' => $existingProduct3]],
            ]);
        
        // Expect persist to be called once for new product P002
        $this->entityManager->expects($this->once())
            ->method('persist');
        
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');

        $this->writer->addToBatch($dto1);
        $this->writer->addToBatch($dto2);
        $this->writer->addToBatch($dto3);
        
        $this->writer->flushBatch();

        $this->assertSame(1, $this->writer->getCreatedCount());
        $this->assertSame(2, $this->writer->getUpdatedCount());
    }

    public function testFlushEmptyBatch(): void
    {
        $this->writer->flushBatch();

        $this->assertSame(0, $this->writer->getCreatedCount());
        $this->assertSame(0, $this->writer->getUpdatedCount());
    }

    public function testAutoFlushWhenBatchSizeReached(): void
    {
        $dto1 = new ProductImportDTO('Product 1', 'Description 1', 'P001', 10, 19.99);
        $dto2 = new ProductImportDTO('Product 2', 'Description 2', 'P002', 20, 29.99);
        
        $this->productRepository->expects($this->once())
            ->method('findByCodes')
            ->willReturn([]);
        
        $this->entityManager->expects($this->exactly(2))
            ->method('persist');
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        // Adding second item should trigger auto-flush due to batch size = 2
        $this->writer->addToBatch($dto1);
        $this->assertSame(1, $this->writer->getCurrentBatchCount());
        
        $this->writer->addToBatch($dto2);
        $this->assertSame(0, $this->writer->getCurrentBatchCount()); // Should be cleared after flush
    }

    public function testUpdateProductFromDTOWithDiscontinuedProduct(): void
    {
        $discontinuedAt = new DateTime('2023-12-31');
        $dto = new ProductImportDTO('Product Name', 'Description', 'P001', 5, 15.99, $discontinuedAt);
        
        $existingProduct = new Product();
        $existingProduct->setCode('P001')->setName('Old Name')->setDescription('Old Description');
        
        $this->writer->addToBatch($dto);
        
        $this->productRepository->expects($this->once())
            ->method('findByCodes')
            ->with(['P001'])
            ->willReturn(['P001' => $existingProduct]);
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->writer->flushBatch();
        
        $this->assertSame('Product Name', $existingProduct->getName());
        $this->assertSame('Description', $existingProduct->getDescription());
        $this->assertSame(5, $existingProduct->getStockLevel());
        $this->assertSame('15.99', $existingProduct->getPrice());
        $this->assertEquals($discontinuedAt, $existingProduct->getDiscontinuedAt());
    }
}