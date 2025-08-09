<?php

declare(strict_types=1);

namespace App\Service\ProductImport;

use App\DTO\ProductImportDTO;

interface BulkWriterInterface
{
    public function addToBatch(ProductImportDTO $dto): void;
    
    public function flushBatch(): int;
    
    public function clearBatch(): void;
    
    public function setBatchSize(int $size): void;
    
    public function getBatchSize(): int;
    
    public function getCurrentBatchCount(): int;
}