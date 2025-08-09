<?php

declare(strict_types=1);

namespace App\Service\ProductImport\Parser;

use App\DTO\ProductImportDTO;

interface FileParserInterface
{
    /**
     * Stream records from file one at a time for memory efficiency
     */
    public function streamRecords(string $filePath): \Generator;

    /**
     * Map a single record to ProductImportDTO
     */
    public function mapRecord(mixed $record): ProductImportDTO;

    /**
     * Check if this parser supports the given file
     */
    public function supports(string $filePath): bool;
}