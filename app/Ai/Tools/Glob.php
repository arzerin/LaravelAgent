<?php

// # ========== 21/Jun/2026 Sunday Added =================
// # TASK Purpose:: Add an AI tool that finds files by glob pattern using Symfony Finder.
// # ========== 21/Jun/2026 Sunday Added =================

namespace App\Ai\Tools;

use Symfony\Component\Finder\Finder;

use function array_values;
use function base_path;
use function json_encode;
use function preg_quote;
use function str_contains;
use function str_replace;
use function str_starts_with;

class Glob implements Tool
{
    public function definition(): array
    {
        return [
            'type' => 'function',
            'name' => 'glob',
            'description' => 'Find files matching a glob pattern, supporting ** for recursive search.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'pattern' => [
                        'type' => 'string',
                        'description' => 'The glob pattern to match against project-relative file paths. Examples: "*.php", "app/**/*.php", "config/*.php".',
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

        if (str_starts_with($pattern, '/') || str_contains($pattern, '..')) {
            return 'Error: Glob pattern must be relative to the project root.';
        }

        $matches = [];
        $regex = $this->globToRegex($pattern);
        $matchBasename = ! str_contains($pattern, '/');

        foreach (Finder::create()->files()->ignoreDotFiles(false)->in(base_path()) as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());

            if (preg_match($regex, $relativePath) || ($matchBasename && preg_match($regex, $file->getFilename()))) {
                $matches[] = $relativePath;
            }
        }

        return json_encode(array_values($matches), JSON_PRETTY_PRINT);
    }

    private function globToRegex(string $pattern): string
    {
        $regex = preg_quote(str_replace('\\', '/', $pattern), '#');
        $regex = str_replace('\*\*/', '(?:.*/)?', $regex);
        $regex = str_replace('\*\*', '.*', $regex);
        $regex = str_replace('\*', '[^/]*', $regex);
        $regex = str_replace('\?', '[^/]', $regex);

        return '#^'.$regex.'$#';
    }
}
