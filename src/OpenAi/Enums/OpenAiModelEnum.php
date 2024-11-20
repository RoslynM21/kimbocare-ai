<?php

namespace Kimbocare\Ai\OpenAi\Enums;

enum OpenAiModelEnum: string
{
    case GPT_4o = "gpt-4o";
    case GPT_4O_mini = "gpt-4o-mini";

    static function toArray(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
