<?php

// # ========== 21/Jun/2026 Sunday Added =================
// # TASK Purpose:: Fix chat history compaction threshold checks and Responses API payload.
// # ========== 21/Jun/2026 Sunday Added =================

namespace App\Ai\Agents;

use App\Ai\Attributes\CompactsAfter;
use App\Ai\Tools\CurrentTime;
use App\Ai\Tools\ReadFile;
use App\Ai\Tools\Tool;
use Illuminate\Support\Facades\Http;
use ReflectionClass;

use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class Agent
{
    protected array $history = [];

    public function instructions(): string
    {
        return 'You are a helpful AI Assistant.';
    }

    public function prompt(string $prompt)
    {
        $this->mayCompact();

        $this->history[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        while (true) {
            $response = $this->runModel();

            $this->history = [...$this->history, ...$response['output']]; // similar array merge

            $toolCalls = collect($response['output'])
                ->filter(fn ($item) => $item['type'] === 'function_call');

            if ($toolCalls->isEmpty()) {
                return $response['output'][0]['content'][0]['text'];

                $this->info($response['output'][0]['content'][0]['text']);
                // dump(json_decode($response['output'][0]['content'][0]['text'], associative: true));
                break;
            }

            $toolCalls->each(function (array $call) {

                // info('Running Tool: '.$call['name'].'('.json_encode($call['arguments']).')');
                info('Running Tool: '.$call['name']);

                $this->runTool($call);
            });

            // dump($response['output']

        }

        // dump($this->history);

    }

    protected function tools(): array
    {
        return [];
    }

    protected function schema(): array
    {
        return [];
    }

    public function messages(): array
    {
        return $this->history;
    }

    /**
     * @param  array  $coll
     */
    public function runTool(array $call): void
    {
        foreach ($this->tools() as $tool) {
            if ($tool->definition()['name'] === $call['name']) {
                $result = $tool->use(json_decode($call['arguments'], associative: true));

                $this->history[] = [
                    'type' => 'function_call_output',
                    'call_id' => $call['call_id'],
                    'output' => (string) $result,
                ];

            }

        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function runModel(): mixed
    {
        return Http::withToken(config('services.openai.key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10)
            ->retry([100, 200])
            ->post('https://api.openai.com/v1/responses', [
                'model' => 'gpt-5.4-nano',
                'instructions' => $this->instructions(), // 'You are a helpful assistant.',
                'input' => $this->history,
                // 'tools' => [
                //     new CurrentTime(),
                //     new ReadFile(),
                // ]
                'tools' => array_map(fn (Tool $tool) => $tool->definition(), $this->tools()),
                'text' => $this->schema() ? [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'assistant_response',
                        'strict' => true,
                        'schema' => $this->schema(),
                    ],
                ] : null,

            ])
            ->throw()
            ->json();
    }

    protected function mayCompact(): void
    {
        // $threshold = $this->getThreshold();

        // if ($config->threshold <= count($this->history)) {
        if ($this->shouldCompact()) {
            // dump('Running now shouldCompact ... ');
            $this->compact($this->history);
        }
    }

    protected function shouldCompact(): bool
    {
        // $threshold = $this->getThreshold();

        return $this->getThreshold() <= count($this->history);
    }

    protected function compact(array $history): void
    {
        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/responses', [
                'model' => 'gpt-5.4-nano',
                'instructions' => 'Summarize the following conversation history concisely, Preserve key facts, decisions, tool results, and unresolved questions. Omit pleasantries and redundant exchanges.',
                'input' => $history,
            ])
            ->throw()
            ->json();
        $summary = $response['output'][0]['content'][0]['text'];

        $this->history = [
            [
                'role' => 'user',
                'content' => '[Earlier Conversation Summary]\n\n'.$summary,
            ],
        ];
        //dump('Compacted conversion to ');

        // dd($this->history);
        //dump($this->history);
    }

    public function getThreshold(): int
    {
        $attributes = (new ReflectionClass($this))->getAttributes(CompactsAfter::class);
        // dd($attributes);
        $config = $attributes
            ? $attributes[0]->newInstance()
            : new CompactsAfter(30);

        return $config->threshold;
    }
}
