<?php

// # ========== 21/Jun/2026 Sunday Added =================
// # TASK Purpose:: Add an AI tool that writes content to a project-relative file.
// # ========== 21/Jun/2026 Sunday Added =================
// # ========== 21/Jun/2026 Sunday Added =================
// # TASK Purpose:: Clarify that the write file tool creates directories as needed.
// # ========== 21/Jun/2026 Sunday Added =================

namespace App\Ai\Tools;

use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function str_contains;
use function str_starts_with;

class WriteFile implements Tool
{
    public function definition(): array
    {
        return [
            'type' => 'function',
            'name' => 'write_file',
            'description' => 'Write content to a file, creating directories as needed.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'The relative path to the file.',
                    ],
                    'content' => [
                        'type' => 'string',
                        'description' => 'The content to write to the file.',
                    ],
                ],
                'required' => ['path', 'content'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];
    }

    public function use(array $arguments = []): string
    {
        $relativePath = $arguments['path'];

        if (str_starts_with($relativePath, '/') || str_contains($relativePath, '..')) {
            return 'Error: File path must be relative to the project root.';
        }

        $path = base_path($relativePath);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $bytes = file_put_contents($path, $arguments['content']);

        if ($bytes === false) {
            return "Error: File could not be written at {$relativePath}.";
        }

        return "File written successfully at {$relativePath}.";
    }
}
