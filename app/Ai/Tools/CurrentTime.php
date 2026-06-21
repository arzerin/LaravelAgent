<?php

namespace App\Ai\Tools;
use App\Ai\Tools\Tool;

class CurrentTime implements Tool
{
    public function definition(): array
    {
        return [
            'type' => 'function',
            'name' => 'get_current_time',
            'description' => 'Get the current server time as an ISO8601 string.',
        ];
    }

    public function use(array $arguments = [])
    {
        return now()->toIso8601String();
    }

}