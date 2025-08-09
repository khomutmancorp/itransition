<?php

declare(strict_types=1);

namespace App\Service\ProductImport\Factory;

use App\Service\ProductImport\Parser\FileParserInterface;

final readonly class ParserFactory
{
    /**
     * @param iterable<FileParserInterface> $parsers
     */
    public function __construct(
        private iterable $parsers
    ) {
    }

    /**
     * Get the appropriate parser for the given file
     */
    public function getParserForFile(string $filePath): FileParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($filePath)) {
                return $parser;
            }
        }

        throw new \InvalidArgumentException("No parser found for file: {$filePath}");
    }

}