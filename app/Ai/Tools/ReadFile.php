<?php

namespace App\Ai\Tools;

use function file_get_contents;

class ReadFile implements Tool
{
    public function definition(): array
    {
        return [
            'type' => 'function',
            'name' => 'read_file',
            'description' => 'Read the contents of a file, relative to the project root.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'The relative path to the file.',
                    ],
                ],
                'required' => ['path'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];
    }

    public function use(array $arguments = [])
    {
        $path = base_path($arguments['path']);

        if (! file_exists($path)) {
            return "Error: File not found at {$arguments['path']}.";
        }

        return file_get_contents($path);

        return file_get_contents(
            base_path($arguments['path'])
        );
    }
}
