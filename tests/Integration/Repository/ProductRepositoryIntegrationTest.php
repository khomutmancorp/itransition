<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ProductRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->em->getRepository(Product::class);

        // Clean up before each test
        $this->em->createQuery('DELETE FROM App\Entity\Product')->execute();
    }

    public function testFindByCodesReturnsCorrectProducts(): void
    {
        $product1 = (new Product())->setCode('P101')->setName('Prod1')->setDescription('D1')->setStockLevel(1)->setPrice('10.00');
        $product2 = (new Product())->setCode('P102')->setName('Prod2')->setDescription('D2')->setStockLevel(2)->setPrice('20.00');
        $this->em->persist($product1);
        $this->em->persist($product2);
        $this->em->flush();

        $result = $this->repository->findByCodes(['P101', 'P102', 'P999']);
        $this->assertArrayHasKey('P101', $result);
        $this->assertArrayHasKey('P102', $result);
        $this->assertArrayNotHasKey('P999', $result);
        $this->assertSame('Prod1', $result['P101']->getName());
        $this->assertSame('Prod2', $result['P102']->getName());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->createQuery('DELETE FROM App\Entity\Product')->execute();
        $this->em->close();
    }
}