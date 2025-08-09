<?php

declare(strict_types=1);

namespace App\Service\ProductImport\Mapper;

final class ProductCsvColumnMapper
{
    // CSV Column Names - Constants for direct use
    public const COLUMN_PRODUCT_CODE = 'Product Code';
    public const COLUMN_PRODUCT_NAME = 'Product Name';
    public const COLUMN_PRODUCT_DESCRIPTION = 'Product Description';
    public const COLUMN_STOCK = 'Stock';
    public const COLUMN_PRICE = 'Cost in GBP';
    public const COLUMN_DISCONTINUED = 'Discontinued';
}