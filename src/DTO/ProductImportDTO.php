<?php

declare(strict_types=1);

namespace App\DTO;

readonly class ProductImportDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public string $code,
        public ?int $stockLevel = null,
        public ?float $price = null,
        public ?\DateTimeInterface $addedAt = null,
        public ?\DateTimeInterface $discontinuedAt = null,
    ) {
    }
}