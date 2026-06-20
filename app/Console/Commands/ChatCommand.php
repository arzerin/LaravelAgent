<?php

## ========== 20/Jun/2026 Saturday Added =================
## TASK Purpose:: Create an Artisan chat command that displays an OpenAI response.
## ========== 20/Jun/2026 Saturday Added =================
## ========== 20/Jun/2026 Saturday Added =================
## TASK Purpose:: Prompt the user for chat input before sending the OpenAI request.
## ========== 20/Jun/2026 Saturday Added =================
## ========== 20/Jun/2026 Saturday Added =================
## TASK Purpose:: Display the chat prompt with the Laravel Prompts field box.
## ========== 20/Jun/2026 Saturday Added =================
## ========== 20/Jun/2026 Saturday Added =================
## TASK Purpose:: Wrap the OpenAI request in a Laravel Prompts spinner.
## ========== 20/Jun/2026 Saturday Added =================

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

#[Signature('chat')]
#[Description('Receive an AI response.')]
class ChatCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (blank(config('services.openai.key'))) {
            $this->error('Please set OPENAI_API_KEY in your environment.');

            return self::FAILURE;
        }

        $prompt = text('What is on your mind?', required: true);

        $response = spin(
            fn (): array => $this->runModel($prompt),
            'Hm... thinking about that.'
        );

        $this->info($response['output'][0]['content'][0]['text']);

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function runModel(string $prompt): array
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
                'input' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ])
            ->throw()
            ->json();
    }
}
