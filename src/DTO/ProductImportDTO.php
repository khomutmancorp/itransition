<?php

declare(strict_types=1);

namespace App\DTO;

use DateTimeInterface;

readonly class ProductImportDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public string $code,
        public ?int $stockLevel = null,
        public ?float $price = null,
        public ?DateTimeInterface $discontinuedAt = null,
    ) {
    }
}