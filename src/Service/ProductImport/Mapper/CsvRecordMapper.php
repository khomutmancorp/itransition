<?php

declare(strict_types=1);

namespace App\Service\ProductImport\Mapper;

use App\DTO\ProductImportDTO;
use App\Service\ProductImport\Mapper\ProductCsvColumnMapper;
use App\Service\ProductImport\ValidationUtils;

final readonly class CsvRecordMapper
{
    // No constructor needed - using constants directly

    public function mapRecordToDTO(array $record): ProductImportDTO
    {
        // Extract and validate required fields
        $code = ValidationUtils::validateRequired(
            $record[ProductCsvColumnMapper::COLUMN_PRODUCT_CODE] ?? '', 
            ProductCsvColumnMapper::COLUMN_PRODUCT_CODE
        );
        
        $name = ValidationUtils::validateRequired(
            $record[ProductCsvColumnMapper::COLUMN_PRODUCT_NAME] ?? '', 
            ProductCsvColumnMapper::COLUMN_PRODUCT_NAME
        );
        
        $description = ValidationUtils::validateRequired(
            $record[ProductCsvColumnMapper::COLUMN_PRODUCT_DESCRIPTION] ?? '', 
            ProductCsvColumnMapper::COLUMN_PRODUCT_DESCRIPTION
        );

        // Extract optional numeric fields
        $stockLevel = ValidationUtils::validateOptionalInt(
            $record[ProductCsvColumnMapper::COLUMN_STOCK] ?? '', 
            ProductCsvColumnMapper::COLUMN_STOCK
        );
        
        $price = ValidationUtils::validateOptionalFloat(
            $record[ProductCsvColumnMapper::COLUMN_PRICE] ?? '', 
            ProductCsvColumnMapper::COLUMN_PRICE
        );

        // Parse discontinued field
        $discontinuedAt = ValidationUtils::parseDiscontinuedDate(
            $record[ProductCsvColumnMapper::COLUMN_DISCONTINUED] ?? ''
        );

        return new ProductImportDTO(
            name: $name,
            description: $description,
            code: $code,
            stockLevel: $stockLevel,
            price: $price,
            addedAt: new \DateTime(),
            discontinuedAt: $discontinuedAt,
        );
    }
}