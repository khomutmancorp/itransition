<?php

declare(strict_types=1);

namespace App\Service\ProductImport\Parser;

use App\DTO\ProductImportDTO;
use App\Service\ProductImport\Mapper\CsvRecordMapper;
use League\Csv\Exception;
use League\Csv\Reader;

readonly class CsvStreamingParser implements FileParserInterface
{
    public function __construct(
        private CsvRecordMapper $recordMapper
    ) {
    }

    public function streamRecords(string $filePath): \Generator
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            // League CSV already streams records efficiently
            // This yields one record at a time without loading entire file
            foreach ($csv->getRecords() as $record) {
                yield $record;
            }
        } catch (Exception $e) {
            throw new \RuntimeException("Failed to read CSV file: " . $e->getMessage(), 0, $e);
        }
    }

    public function mapRecord(mixed $record): ProductImportDTO
    {
        return $this->recordMapper->mapRecordToDTO($record);
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $extension === 'csv';
    }
}