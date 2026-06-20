<!--
## ========== 21/Jun/2026 Sunday Added =================
## TASK Purpose:: Document the Laravel AI chat command setup from installation to execution.
## ========== 21/Jun/2026 Sunday Added =================
-->

# Laravel AI Chat Agent

## Artisan-powered OpenAI chat command using Laravel Prompts

This Laravel project adds a custom Artisan command, `php artisan chat`, that asks the user what is on their mind, sends the prompt to OpenAI's Responses API, and prints the assistant response in the terminal.

The command uses:

- Laravel 13
- PHP 8.3+
- Laravel Prompts `text()` input field
- Laravel Prompts `spin()` loading indicator
- Laravel HTTP Client
- OpenAI Responses API
- Pest tests

## Requirements

- PHP `>= 8.3`
- Composer
- Node.js and npm
- SQLite or another Laravel-supported database
- An OpenAI API key

Check your PHP version:

```bash
php -v
```

If you use Laravel Herd, make sure your terminal resolves Herd's PHP 8.3 binary:

```bash
which php
php -v
```

You can run Artisan directly with Herd PHP if needed:

```bash
"/Users/jerin/Library/Application Support/Herd/bin/php" artisan chat
```

## Project Setup From Start

Clone the repository:

```bash
git clone <your-repository-url>
cd LaravelAgent
```

Install Composer dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the Laravel app key:

```bash
php artisan key:generate
```

Install npm dependencies:

```bash
npm install
```

Build frontend assets:

```bash
npm run build
```

Run migrations:

```bash
php artisan migrate
```

You can also use the project setup script:

```bash
composer run setup
```

## Install OpenAI Libraries

This project uses Laravel's HTTP client for the actual Responses API request. If you also want the OpenAI PHP/Laravel packages installed, use:

```bash
composer require openai-php/client openai-php/laravel
```

The project also uses Laravel Prompts, which is included with modern Laravel applications. If needed, install it with:

```bash
composer require laravel/prompts
```

## Configure OpenAI API Key

Add your OpenAI API key to `.env`:

```env
OPENAI_API_KEY=your_openai_api_key_here
```

Never commit your real `.env` file or API key to GitHub.

## Configure `config/services.php`

Add the OpenAI service key configuration:

```php
'openai' => [
    'key' => env('OPENAI_API_KEY'),
],
```

This allows the command to read the key with:

```php
config('services.openai.key')
```

## Create The Chat Command

Create the command file with Artisan:

```bash
php artisan make:command ChatCommand --no-interaction
```

This creates:

```text
app/Console/Commands/ChatCommand.php
```

The command signature should be:

```php
#[Signature('chat')]
#[Description('Receive an AI response.')]
```

Then the command can be run with:

```bash
php artisan chat
```

## `ChatCommand.php` Example

The command uses Laravel Prompts for a polished terminal input field and spinner:

```php
<?php

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
```

## Run The Chat Command

Run:

```bash
php artisan chat
```

You will see a Laravel Prompts input box:

```text
What is on your mind?
```

Enter a prompt, then the command will show:

```text
Hm... thinking about that.
```

After the API request finishes, the assistant response is printed in the terminal.

## Create The Test File

Create the Pest test:

```bash
php artisan make:test --pest ChatCommandTest --no-interaction
```

This creates:

```text
tests/Feature/ChatCommandTest.php
```

The test should fake the OpenAI request so tests do not call the real API:

```php
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('chat command outputs an openai response', function () {
    config(['services.openai.key' => 'test-openai-key']);

    Http::preventStrayRequests();
    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output' => [
                [
                    'content' => [
                        [
                            'type' => 'output_text',
                            'text' => 'I am doing well today.',
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $this->artisan('chat')
        ->expectsQuestion('What is on your mind?', 'How are you today?')
        ->expectsOutput('I am doing well today.')
        ->assertSuccessful();

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.openai.com/v1/responses'
            && $request->hasHeader('Authorization', 'Bearer test-openai-key')
            && $request['model'] === 'gpt-5.4-nano'
            && $request['instructions'] === 'You are a helpful assistant.'
            && $request['input'] === [
                ['role' => 'user', 'content' => 'How are you today?'],
            ];
    });
});
```

## Run Tests

Run the focused command test:

```bash
php artisan test --compact --filter=ChatCommandTest
```

Run all tests:

```bash
php artisan test --compact
```

Or use Composer:

```bash
composer test
```

## Format PHP Code

Run Laravel Pint after PHP changes:

```bash
vendor/bin/pint --dirty --format agent
```

## Useful Artisan Commands

List Artisan commands:

```bash
php artisan list
```

Confirm the chat command is registered:

```bash
php artisan list | grep chat
```

Clear cached configuration after changing `.env`:

```bash
php artisan config:clear
```

Run the command:

```bash
php artisan chat
```

## Troubleshooting

### PHP version error

If you see:

```text
Your Composer dependencies require a PHP version ">= 8.3.0".
```

Your terminal is using an older PHP version. Check:

```bash
which php
php -v
```

With Laravel Herd, add Herd's PHP path to your shell profile:

```bash
export PATH="/Users/jerin/Library/Application Support/Herd/bin:$PATH"
```

Then reload your shell:

```bash
source ~/.zshrc
```

### Missing OpenAI API key

If the command says:

```text
Please set OPENAI_API_KEY in your environment.
```

Add the key to `.env`:

```env
OPENAI_API_KEY=your_openai_api_key_here
```

Then clear config:

```bash
php artisan config:clear
```

### Laravel Prompts does not show the field box

Run the command in an interactive terminal:

```bash
php artisan chat
```

Do not pipe input into the command when you want the Laravel Prompts field-box UI:

```bash
printf 'Hello\n' | php artisan chat
```

Laravel Prompts is designed for interactive terminal usage.

## GitHub Push Checklist

Before pushing:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
git status
```

Make sure `.env` is not committed.

Commit and push:

```bash
git add .
git commit -m "Add Laravel AI chat command"
git push
```

## License

This project is open-sourced software licensed under the MIT license.
