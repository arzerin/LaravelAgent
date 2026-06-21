<?php

// # ========== 21/Jun/2026 Sunday Added =================
// # TASK Purpose:: Add an AI tool that searches text across project files with capped results.
// # ========== 21/Jun/2026 Sunday Added =================

namespace App\Ai\Tools;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_values;
use function explode;
use function file_get_contents;
use function is_file;
use function json_encode;
use function str_contains;
use function str_replace;

class SearchFile implements Tool
{
    private const MAX_RESULTS = 50;

    public function definition(): array
    {
        return [
            'type' => 'function',
            'name' => 'search_file',
            'description' => 'Search for a text pattern across project files, returning matches with file paths and line numbers.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'pattern' => [
                        'type' => 'string',
                        'description' => 'The text pattern to search for.',
                    ],
                ],
                'required' => ['pattern'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];
    }

    public function use(array $arguments = []): string
    {
        $pattern = $arguments['pattern'];
        $matches = [];

        foreach ($this->projectFiles() as $file) {
            $contents = file_get_contents($file->getPathname());

            if ($contents === false || ! str_contains($contents, $pattern)) {
                continue;
            }

            foreach (explode("\n", $contents) as $index => $line) {
                if (! str_contains($line, $pattern)) {
                    continue;
                }

                $matches[] = [
                    'path' => $this->relativePath($file),
                    'line' => $index + 1,
                    'text' => $line,
                ];

                if (count($matches) >= self::MAX_RESULTS) {
                    return json_encode(array_values($matches), JSON_PRETTY_PRINT);
                }
            }
        }

        return json_encode(array_values($matches), JSON_PRETTY_PRINT);
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function projectFiles(): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path(), RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if ($this->isExcluded($file) || ! is_file($file->getPathname())) {
                continue;
            }

            yield $file;
        }
    }

    private function isExcluded(SplFileInfo $file): bool
    {
        $relativePath = $this->relativePath($file);

        return str_contains($relativePath, 'vendor/')
            || str_contains($relativePath, 'node_modules/')
            || str_contains($relativePath, '.git/');
    }

    private function relativePath(SplFileInfo $file): string
    {
        return str_replace('\\', '/', str_replace(base_path().'/', '', $file->getPathname()));
    }
}
