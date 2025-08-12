<?php

declare(strict_types=1);

namespace App\Command;

use App\DTO\ImportProducts\ImportResult;
use App\Service\ProductImport\ProductImportService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-products',
    description: 'Import products from file',
)]
class ImportProductsCommand extends Command
{
    public function __construct(
        private readonly ProductImportService $importService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'Path to the file to import (CSV, TXT, JSON)')
            ->addOption('test-mode', null, InputOption::VALUE_NONE, 'Run in test mode without persisting data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $filename = $input->getArgument('filename');
        $testMode = $input->getOption('test-mode');
        
        $io->title('Product Import');
        
        if ($testMode) {
            $io->note('Running in test mode - no data will be persisted');
        }
        
        $io->section('Processing file: ' . basename($filename));
        
        try {
            $result = $this->importService->execute($filename, $testMode);
        } catch (Exception $e) {
            $result = new ImportResult(
                processed: 0,
                created: 0,
                updated: 0,
                skipped: 0,
                errors: [$e->getMessage()]
            );
        }
        
        // Create results table
        $io->table(
            ['Metric', 'Count'],
            [
                ['Items processed', $result->processed],
                ['Items successful', $result->getSuccess()],
                ['Items skipped', $result->skipped],
                ['Errors', count($result->errors)],
            ]
        );
        
        // Show success message
        if ($result->getSuccess() > 0) {
            $io->success(sprintf('Successfully imported %d products!', $result->getSuccess()));
        }
        
        // Show errors if any
        if ($result->hasErrors()) {
            $io->error('Import completed with errors:');
            $io->listing($result->errors);
        }
        
        // Show warning for skipped items
        if ($result->skipped > 0 && !$result->hasErrors()) {
            $io->warning(sprintf('%d items were skipped', $result->skipped));
        }
        
        return Command::SUCCESS;
    }
}