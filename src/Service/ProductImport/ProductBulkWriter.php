<?php

declare(strict_types=1);

namespace App\Service\ProductImport;

use App\DTO\ProductImportDTO;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductBulkWriter implements BulkWriterInterface
{
    private array $batch = [];
    private int $createdCount = 0;
    private int $updatedCount = 0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly int $batchSize
    ) {
    }

    public function addToBatch(ProductImportDTO $dto): void
    {
        $this->batch[] = $dto;

        if ($this->getCurrentBatchCount() >= $this->batchSize) {
            $this->flushBatch();
        }
    }

    public function flushBatch(): void
    {
        if (empty($this->batch)) {
            return;
        }

        // Extract codes from current batch for bulk lookup
        $codes = array_map(static fn($dto) => $dto->code, $this->batch);
        $existingProducts = $this->productRepository->findByCodes($codes);

        foreach ($this->batch as $dto) {
            if (isset($existingProducts[$dto->code])) {
                // Update existing product
                $product = $existingProducts[$dto->code];
                $this->updateProductFromDTO($product, $dto);
                $this->updatedCount++;
            } else {
                // Create new product
                $product = $this->createEntityFromDTO($dto);
                $this->entityManager->persist($product);
                $this->createdCount++;
            }
        }

        $this->entityManager->flush();
        $count = count($this->batch);
        $this->clearBatch();
    }

    public function clearBatch(): void
    {
        $this->batch = [];
        $this->entityManager->clear();
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getCurrentBatchCount(): int
    {
        return count($this->batch);
    }

    private function createEntityFromDTO(ProductImportDTO $dto): Product
    {
        $entity = new Product();
        $entity->setCode($dto->code); // Set code only for new products
        $this->updateProductFromDTO($entity, $dto);

        return $entity;
    }

    private function updateProductFromDTO(Product $product, ProductImportDTO $dto): void
    {
        $product->setName($dto->name);
        $product->setDescription($dto->description);
        $product->setStockLevel($dto->stockLevel);
        $product->setPrice($dto->price !== null ? (string) $dto->price : null);

        if ($dto->discontinuedAt !== null) {
            $product->setDiscontinuedAt($dto->discontinuedAt);
        }
    }
}