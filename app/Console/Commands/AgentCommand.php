<?php


namespace App\Console\Commands;

use App\Ai\Tools\CurrentTime;
use App\Ai\Tools\ReadFile;
use App\Ai\Tools\Tool;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;


use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;


use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

#[Signature('agent')]
#[Description('Converse with OpenAI using Tools.')]
class AgentCommand extends Command
{
    /**
     * @var array<int, array{role: string, content: string}>
     */
    protected array $history = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (blank(config('services.openai.key'))) {
            $this->error('Please set OPENAI_API_KEY in your environment.');

            return self::FAILURE;
        }

        while (true) {

            $prompt = text('What is on your mind?', required: true);

            $this->history[] = [
                'role'    => 'user',
                'content' => $prompt,
            ];

            while (true) {
                $response = spin(
                    fn (): array => $this->runModel(),
                    'Hm... thinking about that.'
                );

                $this->history = [...$this->history, ...$response['output']]; // similar array merge

                $functionCalls = collect($response['output'])
                                    ->filter(fn ($item) => $item['type'] === 'function_call');

                if ($functionCalls->isEmpty()) {
                    $this->info($response['output'][0]['content'][0]['text']);
                    break;
                }

                $functionCalls->each(function (array $call) {

                    info('Running Tool: '.$call['name'].'('.json_encode($call['arguments']).')');

                    foreach($this->tools() as $tool)
                    {
                        if ($tool->definition()['name'] === $call['name']) {
                            $result  = $tool->use(json_decode($call['arguments'], associative: true));

                            $this->history[] = [
                                'type'    => 'function_call_output',
                                'call_id' => $call['call_id'],
                                'output'  => $result,
                            ];

                        }

                    }
                    /*
                    if ($call['name'] === 'get_current_time') {

                        $this->history[] = [
                            'type'    => 'function_call_output',
                            'call_id' => $call['call_id'],
                            'output'  => now()->toIso8601String(),
                        ];
                    }

                    // dump($call);

                    if ($call['name'] === 'read_file') {

                        $this->history[] = [
                            'type'    => 'function_call_output',
                            'call_id' => $call['call_id'],
                            'output'  => file_get_contents(
                                base_path(json_decode($call['arguments'])->path)
                            ),

                        ];
                    }
                    */
                });

                // dump($response['output']

            }

            // dump($this->history);

        }

        return self::SUCCESS;
    }

    /**
     * Execute the console command.
     */
    /*
    public function handle_v2(): int
    {
        if (blank(config('services.openai.key'))) {
            $this->error('Please set OPENAI_API_KEY in your environment.');

            return self::FAILURE;
        }

        while (true) {

            $prompt = text('What is on your mind?', required: true);

            $this->history[] = [
                'role'    => 'user',
                'content' => $prompt,
            ];

            while (true) {
                $response = spin(
                    fn (): array => $this->runModel(),
                    'Hm... thinking about that.'
                );

                $this->history = [...$this->history, ...$response['output']]; // similar array merge

                $functionCalls = collect($response['output'])
                                    ->filter(fn ($item) => $item['type'] === 'function_call');

                if ($functionCalls->isEmpty()) {
                    $this->info($response['output'][0]['content'][0]['text']);
                    break;
                }

                $functionCalls->each(function (array $call) {

                    info('Running Tool: '.$call['name'].'('.json_encode($call['arguments']).')');

                    if ($call['name'] === 'get_current_time') {

                        $this->history[] = [
                            'type'    => 'function_call_output',
                            'call_id' => $call['call_id'],
                            'output'  => now()->toIso8601String(),
                        ];
                    }

                    // dump($call);

                    if ($call['name'] === 'read_file') {

                        $this->history[] = [
                            'type'    => 'function_call_output',
                            'call_id' => $call['call_id'],
                            'output'  => file_get_contents(
                                base_path(json_decode($call['arguments'])->path)
                            ),

                        ];
                    }
                });

                // dump($response['output']

            }

            // dump($this->history);

        }

        return self::SUCCESS;
    }
        */

    /**
     * Execute the console command.
     * Version 1 - use the top one which is more perfect
     */
    /*
    public function handle_v1(): int
    {
        if (blank(config('services.openai.key'))) {
            $this->error('Please set OPENAI_API_KEY in your environment.');

            return self::FAILURE;
        }

        while (true) {

            $prompt = text('What is on your mind?', required: true);

            $this->history[] = [
                'role' => 'user',
                'content' => $prompt,
            ];

            while (true) {
                $response = spin(
                    fn (): array => $this->runModel(),
                    'Hm... thinking about that.'
                );

                $this->history = [...$this->history, ...$response['output']]; // similar array merge

                // AI is saying .. hey can I run get_current_time tool?

                if ($response['output'][0]['type'] === 'function_call') {

                    $call = $response['output'][0];

                    if ($call['name'] === 'get_current_time') {

                        $this->history[] = [
                            'type'    => 'function_call_output',
                            'call_id' => $call['call_id'],
                            'output'  => now()->toIso8601String(),
                        ];
                    }

                    // dump($call);

                    if ($call['name'] === 'read_file') {

                        $this->history[] = [
                            'type'    => 'function_call_output',
                            'call_id' => $call['call_id'],
                            'output'  => file_get_contents(
                                base_path(json_decode($call['arguments'])->path)
                            ),

                        ];
                    }
                } else {
                    // $this->info($response['output'][0]['content'][0]['text']);
                    // break;
                }

            }

            // dump($this->history);

            $this->info($response['output'][0]['content'][0]['text']);
        }

        return self::SUCCESS;
    }
    */

    /**
     * @return array<string, mixed>
     */
    private function runModel(): array
    {
        return Http::withToken(config('services.openai.key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10)
            ->retry([100, 200])
            ->post('https://api.openai.com/v1/responses', [
                'model' => 'gpt-5.4-nano',
                'instructions' => 'You are a helpful assistant.',
                'input' => $this->history,
                // 'tools' => [
                //     new CurrentTime(),
                //     new ReadFile(),
                // ]
                'tools' => array_map(fn (Tool $tool) => $tool->definition(), $this->tools()),
            ])
            ->throw()
            ->json();
    }

    /**
     * @return array
     */
    public function tools(): array
    {
        return [
            new CurrentTime,
            new ReadFile,
        ];
    }

    /*
    private function runModel_v2(): array
    {
        return Http::withToken(config('services.openai.key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10)
            ->retry([100, 200])
            ->post('https://api.openai.com/v1/responses', [
                'model' => 'gpt-5.4-nano',
                'instructions' => 'You are a helpful assistant.',
                'input' => $this->history,
                'tools' => [
                    [
                        'type' => 'function',
                        'name' => 'get_current_time',
                        'description' => 'Get the current server time as an ISO8601 string.',
                    ],
                    [
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
                    ],
                ],
            ])
            ->throw()
            ->json();
    }
    */        

    /*
    private function runModel_v1(): array
    {
        return Http::withToken(config('services.openai.key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10)
            ->retry([100, 200])
            ->post('https://api.openai.com/v1/responses', [
                'model' => 'gpt-5.4-nano',
                'instructions' => 'You are a helpful assistant.',
                'input' => $this->history,
                'tools' => [
                    [
                        'type'        => 'function',
                        'name'        => 'get_current_time',
                        'description' => 'Get the current server time as an ISO8601 string.',
                    ],
                    [
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
                    ],
                ],
            ])
            ->throw()
            ->json();
    }
    */        
}
