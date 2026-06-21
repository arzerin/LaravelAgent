<?php

namespace App\Ai\Tools;


use function file_get_contents;
use function json_decode;

class ReadFile implements Tool
{
    public function definition(): array
    {
        return [
            'type' => 'function',
            'name' => 'read_file',
            'description' => 'Read the contents of a file, relative to the project root.',
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'path' => [
                        'type'        => 'string',
                        'description' => 'The relative path to the file.',
                    ],
                ],
                'required'             => ['path'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];
    }

    public function use(array $arguments = [])
    {
        return file_get_contents(
            base_path($arguments['path'])
        );
    }

}