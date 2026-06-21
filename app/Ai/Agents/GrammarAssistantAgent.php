<?php


namespace App\Ai\Agents;

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

use App\Ai\Agents\Agent;

use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;


class GrammarAssistantAgent extends Agent
{
    public function instructions(): string
    {
        return 'You are helpful grammar AI assistant for third grade. Your sole job is to convert a prompt a sentence given via the prompt into its various grammatical parts.';
    }

    public function tools(): array
    {
        return [
            new CurrentTime,
            new ReadFile,
            new Revenue,
        ];
    }
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                //'response'   => ['type' => 'string'],
                'nouns'      => ['type' => 'string'],
                'adjectives' => ['type' => 'string'],
                'verbs'      => ['type' => 'string'],
            ],
            //'required'             => ['response'],
            'required'             => ['nouns', 'adjectives', 'verbs'],
            'additionalProperties' => false,
        ];
    }


}
