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

class ImportProductsCommandIntegrationTest extends KernelTestCase
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

    final public function testCommandExecutionWithRealFile(): void
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

    final public function testCommandWithTestModeDoesNotPersistData(): void
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

    final public function testCommandWithNormalModePersistsData(): void
    {
        $initialCount = $this->entityManager->getRepository(Product::class)->count();

        $this->commandTester->execute([
            'filename' => $this->testFilePath
        ]);

        $this->assertEquals(ImportProductsCommand::SUCCESS, $this->commandTester->getStatusCode());
        
        $finalCount = $this->entityManager->getRepository(Product::class)->count();
        $this->assertGreaterThan($initialCount, $finalCount, 'Products should be persisted in normal mode');
    }

    final public function testCommandWithNonExistentFile(): void
    {
        $this->commandTester->execute([
            'filename' => '/non/existent/file.csv'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Import completed with errors', $output);
    }

    final public function testCommandArgumentAndOptionConfiguration(): void
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

    final public function testCommandName(): void
    {
        $kernel = self::bootKernel();
        $importService = $kernel->getContainer()->get(ProductImportService::class);
        $command = new ImportProductsCommand($importService);
        $this->assertEquals('app:import-products', $command->getName());
    }

    final public function testCommandDisplaysProcessingResults(): void
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
}