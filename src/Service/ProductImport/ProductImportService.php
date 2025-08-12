<?php

declare(strict_types=1);

namespace App\Service\ProductImport;

use App\DTO\ImportProducts\ImportResult;
use App\Service\ProductImport\Batch\BatchProcessor;
use App\Service\ProductImport\Factory\ParserFactory;
use App\Service\ProductImport\Interface\SkippingRulesSetInterface;
use App\Service\ProductImport\Statistics\ImportStatistics;
use Exception;
use RuntimeException;

readonly class ProductImportService
{
    public function __construct(
        private ParserFactory $parserFactory,
        private BatchProcessor $batchProcessor,
        private SkippingRulesSetInterface $skippingRules
    ) {
    }

    public function execute(string $filePath, bool $testMode = false): ImportResult
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        // Get appropriate parser for file type
        $parser = $this->parserFactory->getParserForFile($filePath);

        $statistics = new ImportStatistics();

        try {
            // Stream processing - one record at a time for memory efficiency
            foreach ($parser->streamRecords($filePath) as $index => $record) {
                $statistics->incrementProcessed();

                try {
                    $dto = $parser->mapRecord($record);

                    // Check if this item should be skipped according to rules
                    if ($this->skippingRules->shouldSkip($dto)) {
                        $statistics->incrementSkipped();
                        continue;
                    }
                    
                    if (!$testMode) {
                        $this->batchProcessor->addToBatch($dto);
                    }
                } catch (Exception $e) {
                    $statistics->addError(sprintf(
                        "Record %s can't be added: %s. Line: %d",
                        json_encode($record, JSON_THROW_ON_ERROR),
                        $e->getMessage(),
                        $index + 1
                    ));
                }
            }
            
            // Flush any remaining items in batch
            if (!$testMode) {
                $this->batchProcessor->flush($statistics);
            }

        } catch (Exception $e) {
            throw new RuntimeException(
                "Failed to process file {$filePath}: " . $e->getMessage(),
                0,
                $e
            );
        }

        return $statistics->toResult();
    }
}