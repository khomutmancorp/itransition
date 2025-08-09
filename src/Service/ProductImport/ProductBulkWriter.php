<?php

declare(strict_types=1);

namespace App\Service\ProductImport;

use App\DTO\ProductImportDTO;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ProductBulkWriter implements BulkWriterInterface
{
    private array $batch = [];

    private int $batchSize;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        int $batchSize
    ) {
        $this->batchSize = $batchSize;
    }

    public function addToBatch(ProductImportDTO $dto): void
    {
        $entity = $this->createEntityFromDTO($dto);
        $this->batch[] = $entity;

        if ($this->getCurrentBatchCount() >= $this->batchSize) {
            $this->flushBatch();
        }
    }

    public function flushBatch(): int
    {
        if (empty($this->batch)) {
            return 0;
        }

        $count = count($this->batch);

        foreach ($this->batch as $entity) {
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
        $this->clearBatch();

        return $count;
    }

    public function clearBatch(): void
    {
        $this->batch = [];
        $this->entityManager->clear();
    }

    public function setBatchSize(int $size): void
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('Batch size must be greater than 0');
        }

        $this->batchSize = $size;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function getCurrentBatchCount(): int
    {
        return count($this->batch);
    }

    private function createEntityFromDTO(ProductImportDTO $dto): Product
    {
        $entity = new Product();
        $entity->setName($dto->name);
        $entity->setDescription($dto->description);
        $entity->setCode($dto->code);
        $entity->setStockLevel($dto->stockLevel);
        $entity->setPrice($dto->price !== null ? (string) $dto->price : null);

        if ($dto->addedAt !== null) {
            $entity->setAddedAt($dto->addedAt);
        }

        if ($dto->discontinuedAt !== null) {
            $entity->setDiscontinuedAt($dto->discontinuedAt);
        }

        return $entity;
    }
}