<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @param string[] $codes
     * @return Product[]
     */
    public function findByCodes(array $codes): array
    {
        if (empty($codes)) {
            return [];
        }

        $qb = $this->createQueryBuilder('p')
            ->where('p.code IN (:codes)')
            ->setParameter('codes', $codes);

        $products = $qb->getQuery()->getResult();

        // Return associative array keyed by code for efficient lookup
        $result = [];
        foreach ($products as $product) {
            $result[$product->getCode()] = $product;
        }

        return $result;
    }
}