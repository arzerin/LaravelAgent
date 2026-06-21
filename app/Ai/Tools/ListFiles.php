<?php

// # ========== 21/Jun/2026 Sunday Added =================
// # TASK Purpose:: Add an AI tool that lists files and directories for a project-relative path.
// # ========== 21/Jun/2026 Sunday Added =================

namespace App\Ai\Tools;

use DirectoryIterator;

use function array_values;
use function is_dir;
use function json_encode;
use function sort;
use function str_contains;
use function str_starts_with;

class ListFiles implements Tool
{
    public function definition(): array
    {
        return [
            'type' => 'function',
            'name' => 'list_files',
            'description' => 'List files and directories at a given path, with directories shown with a trailing slash.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'The relative directory path to list. Use "." for the project root.',
                    ],
                ],
                'required' => ['path'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];
    }

    public function use(array $arguments = []): string
    {
        $relativePath = $arguments['path'];

        if ($relativePath === '.') {
            $relativePath = '';
        }

        if (str_starts_with($relativePath, '/') || str_contains($relativePath, '..')) {
            return 'Error: Path must be relative to the project root.';
        }

        $path = base_path($relativePath);

        if (! is_dir($path)) {
            return "Error: Directory not found at {$arguments['path']}.";
        }

        $entries = [];

        foreach (new DirectoryIterator($path) as $entry) {
            if ($entry->isDot()) {
                continue;
            }

            $entries[] = $entry->getFilename().($entry->isDir() ? '/' : '');
        }

        sort($entries);

        return json_encode(array_values($entries), JSON_PRETTY_PRINT);
    }
}
