<?php

// # ========== 21/Jun/2026 Sunday Added =================
// # TASK Purpose:: Register the dialog Artisan command with Laravel Prompts and OpenAI.
// # ========== 21/Jun/2026 Sunday Added =================

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

#[Signature('dialog')]
#[Description('Converse with OpenAI.')]
class DialogCommand extends Command
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
                'role' => 'user',
                'content' => $prompt,
            ];

            $response = spin(
                fn (): array => $this->runModel(),
                'Hm... thinking about that.'
            );

            //dump($response['output']
            $this->history = [...$this->history, ...$response['output']]; //similar array merge

            dump($this->history);

            $this->info($response['output'][0]['content'][0]['text']);
        }


        return self::SUCCESS;
    }

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
            ])
            ->throw()
            ->json();
    }
}
