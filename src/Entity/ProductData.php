<?php

namespace App\Entity;

use App\Repository\ProductDataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductDataRepository::class)]
#[ORM\Table(name: 'tblProductData')]
#[ORM\HasLifecycleCallbacks]
class ProductData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'intProductDataId', type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $intProductDataId = null;

    #[ORM\Column(name: 'strProductName', length: 50)]
    private ?string $strProductName = null;

    #[ORM\Column(name: 'strProductDesc', length: 255)]
    private ?string $strProductDesc = null;

    #[ORM\Column(name: 'strProductCode', length: 10, unique: true)]
    private ?string $strProductCode = null;

    #[ORM\Column(name: 'dtmAdded', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dtmAdded = null;

    #[ORM\Column(name: 'dtmDiscontinued', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dtmDiscontinued = null;

    #[ORM\Column(name: 'stmTimestamp', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $stmTimestamp = null;

    public function getIntProductDataId(): ?int
    {
        return $this->intProductDataId;
    }

    public function getStrProductName(): ?string
    {
        return $this->strProductName;
    }

    public function setStrProductName(string $strProductName): static
    {
        $this->strProductName = $strProductName;

        return $this;
    }

    public function getStrProductDesc(): ?string
    {
        return $this->strProductDesc;
    }

    public function setStrProductDesc(string $strProductDesc): static
    {
        $this->strProductDesc = $strProductDesc;

        return $this;
    }

    public function getStrProductCode(): ?string
    {
        return $this->strProductCode;
    }

    public function setStrProductCode(string $strProductCode): static
    {
        $this->strProductCode = $strProductCode;

        return $this;
    }

    public function getDtmAdded(): ?\DateTimeInterface
    {
        return $this->dtmAdded;
    }

    public function setDtmAdded(?\DateTimeInterface $dtmAdded): static
    {
        $this->dtmAdded = $dtmAdded;

        return $this;
    }

    public function getDtmDiscontinued(): ?\DateTimeInterface
    {
        return $this->dtmDiscontinued;
    }

    public function setDtmDiscontinued(?\DateTimeInterface $dtmDiscontinued): static
    {
        $this->dtmDiscontinued = $dtmDiscontinued;

        return $this;
    }

    public function getStmTimestamp(): ?\DateTimeInterface
    {
        return $this->stmTimestamp;
    }

    public function setStmTimestamp(\DateTimeInterface $stmTimestamp): static
    {
        $this->stmTimestamp = $stmTimestamp;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->stmTimestamp === null) {
            $this->stmTimestamp = new \DateTime();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->stmTimestamp = new \DateTime();
    }
}