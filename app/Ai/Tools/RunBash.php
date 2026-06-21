<?php

namespace App\Ai\Tools;

use Symfony\Component\Process\Process;

class RunBash implements Tool
{
    public function definition(): array
    {
        return [
            'type' => 'function',
            'name' => 'run_bash',
            'description' => 'Execute a bash command and return its output',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'command' => [
                        'type' => 'string',
                        'description' => 'The bash command to execute',
                    ], // // run_bash('pest tests/Features/FooTest.php')
                ],
                'required' => ['command'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];
    }

    public function use(array $arguments = []): string
    {
        $process = Process::fromShellCommandLine(
            $arguments['command'],
            base_path(),
            timeout: 30
        );

        $process->run();

        if (! $process->isSuccessful()) {
            return $process->getErrorOutput() ?? $process->getOutput();
        }

        return $process->getOutput();
    }
}
