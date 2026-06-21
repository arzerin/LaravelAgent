<?php

namespace App\Ai\Tools;

class Revenue implements Tool
{
    public function definition(): array
    {
        return [
            'type' => 'function',
            'name' => 'site_revenue',
            'description' => 'Get Revenue detail',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'period' => [
                        'type' => 'string',
                        'enum' => ['daily', 'monthly', 'quarterly'],
                        'description' => 'The period of time',
                    ],
                ],
                'required' => ['period'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];
    }

    public function use(array $arguments = []): string
    {
        $period = $arguments['period'];

        return [
            'daily' => 900,
            'monthly' => 30000,
            'quarterly' => 120000,
            'yearly' => 120000 * 4,
        ][$period];
    }
}
