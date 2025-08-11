<?php

namespace App\Tests\Integration\Command;

use App\Command\ImportProductsCommand;
use App\Entity\Product;
use App\Service\ProductImport\ProductImportService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class ImportProductsCommandIntegrationTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private EntityManagerInterface $entityManager;
    private Filesystem $filesystem;
    private string $testFilePath;
    private string $uploadsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->filesystem = new Filesystem();
        $this->uploadsDir = $kernel->getProjectDir() . '/var/uploads';
        $this->testFilePath = $this->uploadsDir . '/test_products.csv';

        $this->createSchema();
        $this->setupTestFiles();

        $application = new Application($kernel);
        $command = $application->find('app:import-products');
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        $this->entityManager->close();
        parent::tearDown();
    }

    private function createSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function setupTestFiles(): void
    {
        if (!$this->filesystem->exists($this->uploadsDir)) {
            $this->filesystem->mkdir($this->uploadsDir);
        }

        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P001,Test Product 1,Description for product 1,10,19.99,\n";
        $csvContent .= "P002,Test Product 2,Description for product 2,25,29.99,\n";
        $csvContent .= "P003,Test Product 3,Description for product 3,5,39.99,yes\n";

        file_put_contents($this->testFilePath, $csvContent);
    }

    private function cleanupTestFiles(): void
    {
        if ($this->filesystem->exists($this->testFilePath)) {
            $this->filesystem->remove($this->testFilePath);
        }
    }

    public function testCommandExecutionWithRealFile(): void
    {
        $this->commandTester->execute([
            'filename' => $this->testFilePath
        ]);

        $this->assertEquals(ImportProductsCommand::SUCCESS, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Product Import', $output);
        $this->assertStringContainsString('test_products.csv', $output);
        $this->assertStringContainsString('Successfully imported', $output);
    }

    public function testCommandWithTestModeDoesNotPersistData(): void
    {
        $initialCount = $this->entityManager->getRepository(Product::class)->count();

        $this->commandTester->execute([
            'filename' => $this->testFilePath,
            '--test-mode' => true
        ]);

        $this->assertEquals(ImportProductsCommand::SUCCESS, $this->commandTester->getStatusCode());
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Running in test mode', $output);
        $this->assertStringContainsString('no data will be persisted', $output);

        $finalCount = $this->entityManager->getRepository(Product::class)->count();
        $this->assertEquals($initialCount, $finalCount, 'No products should be persisted in test mode');
    }

    public function testCommandWithNormalModePersistsData(): void
    {
        $initialCount = $this->entityManager->getRepository(Product::class)->count();

        $this->commandTester->execute([
            'filename' => $this->testFilePath
        ]);

        $this->assertEquals(ImportProductsCommand::SUCCESS, $this->commandTester->getStatusCode());
        
        $finalCount = $this->entityManager->getRepository(Product::class)->count();
        $this->assertGreaterThan($initialCount, $finalCount, 'Products should be persisted in normal mode');
    }

    public function testCommandWithNonExistentFile(): void
    {
        $this->commandTester->execute([
            'filename' => '/non/existent/file.csv'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Import completed with errors', $output);
    }

    public function testCommandArgumentAndOptionConfiguration(): void
    {
        $kernel = self::bootKernel();
        $importService = $kernel->getContainer()->get(ProductImportService::class);
        $command = new ImportProductsCommand($importService);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('filename'));
        $this->assertTrue($definition->hasOption('test-mode'));

        $filenameArgument = $definition->getArgument('filename');
        $this->assertTrue($filenameArgument->isRequired());
        $this->assertEquals('Path to the file to import (CSV, TXT, JSON)', $filenameArgument->getDescription());

        $testModeOption = $definition->getOption('test-mode');
        $this->assertFalse($testModeOption->acceptValue());
        $this->assertEquals('Run in test mode without persisting data', $testModeOption->getDescription());
    }

    public function testCommandName(): void
    {
        $kernel = self::bootKernel();
        $importService = $kernel->getContainer()->get(ProductImportService::class);
        $command = new ImportProductsCommand($importService);
        $this->assertEquals('app:import-products', $command->getName());
    }

    public function testCommandDisplaysProcessingResults(): void
    {
        $this->commandTester->execute([
            'filename' => $this->testFilePath,
            '--test-mode' => true
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Items processed', $output);
        $this->assertStringContainsString('Items successful', $output);
        $this->assertStringContainsString('Items skipped', $output);
    }

    public function testUpsertFunctionalityWithExistingProducts(): void
    {
        // First import - creates new products
        $this->commandTester->execute([
            'filename' => $this->testFilePath
        ]);

        $this->assertEquals(ImportProductsCommand::SUCCESS, $this->commandTester->getStatusCode());
        
        $initialCount = $this->entityManager->getRepository(Product::class)->count();
        $this->assertGreaterThan(0, $initialCount);

        // Verify first product exists with original data
        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['code' => 'P001']);
        $this->assertNotNull($product);
        $originalUpdatedAt = $product->getUpdatedAt();

        // Second import with updated CSV - should update existing products
        $updatedCsvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $updatedCsvContent .= "P001,Updated Test Product 1,Updated description for product 1,15,24.99,\n";
        $updatedCsvContent .= "P002,Updated Test Product 2,Updated description for product 2,30,34.99,\n";
        $updatedCsvContent .= "P004,New Test Product 4,Description for new product 4,8,14.99,\n";

        $updatedFilePath = $this->uploadsDir . '/updated_products.csv';
        file_put_contents($updatedFilePath, $updatedCsvContent);

        // Wait a moment to ensure updated_at timestamp changes
        sleep(1);

        $this->commandTester->execute([
            'filename' => $updatedFilePath
        ]);

        $this->assertEquals(ImportProductsCommand::SUCCESS, $this->commandTester->getStatusCode());
        
        $finalCount = $this->entityManager->getRepository(Product::class)->count();
        
        // Should have one more product (P004 is new, P001 and P002 updated)
        $this->assertEquals($initialCount + 1, $finalCount);

        // Verify P001 was updated
        $this->entityManager->clear(); // Clear entity manager to fetch fresh data
        $updatedProduct = $this->entityManager->getRepository(Product::class)->findOneBy(['code' => 'P001']);
        $this->assertNotNull($updatedProduct);
        $this->assertEquals('Updated Test Product 1', $updatedProduct->getName());
        $this->assertEquals('Updated description for product 1', $updatedProduct->getDescription());
        $this->assertEquals(15, $updatedProduct->getStockLevel());
        $this->assertEquals('24.99', $updatedProduct->getPrice());
        $this->assertGreaterThan($originalUpdatedAt, $updatedProduct->getUpdatedAt());

        // Verify P004 was created
        $newProduct = $this->entityManager->getRepository(Product::class)->findOneBy(['code' => 'P004']);
        $this->assertNotNull($newProduct);
        $this->assertEquals('New Test Product 4', $newProduct->getName());

        // Cleanup
        if ($this->filesystem->exists($updatedFilePath)) {
            $this->filesystem->remove($updatedFilePath);
        }
    }

    public function testImportDisplaysCreatedAndUpdatedCounts(): void
    {
        // Create initial products
        $this->commandTester->execute([
            'filename' => $this->testFilePath
        ]);

        // Create updated CSV with some existing and some new products
        $mixedCsvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $mixedCsvContent .= "P001,Updated Product 1,Updated description,20,29.99,\n"; // Update
        $mixedCsvContent .= "P005,New Product 5,New description,10,19.99,\n"; // Create
        $mixedCsvContent .= "P006,New Product 6,Another new description,5,9.99,\n"; // Create

        $mixedFilePath = $this->uploadsDir . '/mixed_products.csv';
        file_put_contents($mixedFilePath, $mixedCsvContent);

        $this->commandTester->execute([
            'filename' => $mixedFilePath
        ]);

        $output = $this->commandTester->getDisplay();
        
        // The output should show separate counts for created and updated
        $this->assertStringContainsString('Items processed    3', $output);
        $this->assertStringContainsString('Items successful   3', $output);

        // Cleanup
        if ($this->filesystem->exists($mixedFilePath)) {
            $this->filesystem->remove($mixedFilePath);
        }
    }
}