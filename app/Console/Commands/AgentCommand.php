<?php


namespace App\Console\Commands;

use App\Ai\Agents\ChatbotAgent;
use App\Ai\Agents\GrammarAssistantAgent;
use App\Ai\Tools\CurrentTime;
use App\Ai\Tools\ReadFile;
use App\Ai\Tools\Revenue;
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
#[Description('Interact as agent')]
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

        $agent = new ChatbotAgent();

//        $agent = new GrammarAssistantAgent();
//        $response = $agent->prompt('The brown dog jumped over the white moon and landed on a gigantic piece of cheese.');
//
//        dump($response);

        while (true)
        {
            $prompt = text('What is on your mind?', required: TRUE);

            $response = spin(
                fn () => $agent->prompt($prompt),
                'Hm... thinking about that.'
            );
            info($response);
        }

        return self::SUCCESS;
    }
}
