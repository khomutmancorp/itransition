<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'name', length: 255)]
    private ?string $name = null;

    #[ORM\Column(name: 'description', type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(name: 'code', length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(name: 'stock_level', type: Types::INTEGER, nullable: true)]
    private ?int $stockLevel = null;

    #[ORM\Column(name: 'price', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(name: 'added_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $addedAt = null;

    #[ORM\Column(name: 'discontinued_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $discontinuedAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getStockLevel(): ?int
    {
        return $this->stockLevel;
    }

    public function setStockLevel(?int $stockLevel): static
    {
        $this->stockLevel = $stockLevel;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getAddedAt(): ?\DateTimeInterface
    {
        return $this->addedAt;
    }

    public function setAddedAt(?\DateTimeInterface $addedAt): static
    {
        $this->addedAt = $addedAt;
        return $this;
    }

    public function getDiscontinuedAt(): ?\DateTimeInterface
    {
        return $this->discontinuedAt;
    }

    public function setDiscontinuedAt(?\DateTimeInterface $discontinuedAt): static
    {
        $this->discontinuedAt = $discontinuedAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->updatedAt === null) {
            $this->updatedAt = new \DateTime();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}