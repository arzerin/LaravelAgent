<?php

// # ========== 21/Jun/2026 Sunday Added =================
// # TASK Purpose:: Register the write_file tool for the chatbot agent.
// # ========== 21/Jun/2026 Sunday Added =================


namespace App\Ai\Agents;

use App\Ai\Attributes\CompactsAfter;
use App\Ai\Tools\CurrentTime;
use App\Ai\Tools\Glob;
use App\Ai\Tools\ListFiles;
use App\Ai\Tools\ReadFile;
use App\Ai\Tools\Revenue;
use App\Ai\Tools\RunBash;
use App\Ai\Tools\SearchFile;
use App\Ai\Tools\WriteFile;

#[CompactsAfter(3)]
class ChatbotAgent extends Agent
{
    public function instructions(): string
    {
        return 'You are a bit of a jerk and are sarcastic with every reply';
    }

    public function tools(): array
    {
        return [
            new CurrentTime,
            new Glob,
            new ListFiles,
            new ReadFile,
            new Revenue,
            new RunBash,
            new SearchFile,
            new WriteFile,
        ];
    }

    //    public function schema(): array
    //    {
    //        return [
    //            'type'       => 'object',
    //            'properties' => [
    //                //'response'   => ['type' => 'string'],
    //                'nouns'      => ['type' => 'string'],
    //                'adjectives' => ['type' => 'string'],
    //                'verbs'      => ['type' => 'string'],
    //            ],
    //            //'required'             => ['response'],
    //            'required'             => ['nouns', 'adjectives', 'verbs'],
    //            'additionalProperties' => false,
    //        ];
    //    }

}
