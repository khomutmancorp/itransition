# Adding New File Format Parsers

## Current Architecture Overview

The product import system uses a factory-based architecture with streaming parsers for memory-efficient processing of large files. The system automatically discovers and registers parsers through Symfony's dependency injection.

## How to Add a New Format Parser

Thanks to the factory-based architecture, adding support for new file formats is straightforward:

### Step 1: Create Parser Class

```php
<?php

declare(strict_types=1);

namespace App\Service\ProductImport\Parser;

use App\DTO\ProductImportDTO;
use App\Service\ProductImport\Mapper\JsonRecordMapper;

final readonly class JsonParser implements FileParserInterface
{
    public function __construct(
        private JsonRecordMapper $recordMapper
    ) {
    }

    public function streamRecords(string $filePath): \Generator
    {
        // JSON streaming implementation using JsonMachine for memory efficiency
        $parser = \JsonMachine\Items::fromFile($filePath);
        foreach ($parser as $record) {
            yield $record;
        }
    }

    public function mapRecord(mixed $record): ProductImportDTO
    {
        return $this->recordMapper->mapRecordToDTO($record);
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $extension === 'json';
    }
}
```

### Step 2: Create Record Mapper

For consistency with CSV format, you can use the same column constants or define your own:

```php
<?php

declare(strict_types=1);

namespace App\Service\ProductImport\Mapper;

use App\DTO\ProductImportDTO;
use App\Service\ProductImport\Mapper\ProductCsvColumnMapper;
use App\Service\ProductImport\ValidationUtils;

final readonly class JsonRecordMapper
{
    // Option 1: Use existing CSV constants for consistency
    public function mapRecordToDTO(array $record): ProductImportDTO
    {
        // Extract and validate required fields using CSV constants
        $code = ValidationUtils::validateRequired(
            $record['productCode'] ?? $record['code'] ?? '', 
            ProductCsvColumnMapper::COLUMN_PRODUCT_CODE
        );
        
        $name = ValidationUtils::validateRequired(
            $record['productName'] ?? $record['name'] ?? '', 
            ProductCsvColumnMapper::COLUMN_PRODUCT_NAME
        );
        
        $description = ValidationUtils::validateRequired(
            $record['productDescription'] ?? $record['description'] ?? '', 
            ProductCsvColumnMapper::COLUMN_PRODUCT_DESCRIPTION
        );

        // Extract optional numeric fields
        $stockLevel = ValidationUtils::validateOptionalInt(
            $record['stock'] ?? $record['stockLevel'] ?? '', 
            ProductCsvColumnMapper::COLUMN_STOCK
        );
        
        $price = ValidationUtils::validateOptionalFloat(
            $record['price'] ?? $record['cost'] ?? '', 
            ProductCsvColumnMapper::COLUMN_PRICE
        );

        // Parse discontinued field
        $discontinuedAt = ValidationUtils::parseDiscontinuedDate(
            $record['discontinued'] ?? ''
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
```

### Step 3: That's It!

The parser will be automatically:
- **Discovered** by Symfony's autowiring
- **Tagged** with `app.file_parser` 
- **Registered** in ParserFactory
- **Available** for import
