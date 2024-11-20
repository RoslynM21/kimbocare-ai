<?php

namespace Kimbocare\Ai\DeepGram\Enums;

enum DeepGramModelEnum: string
{
    case Nova2 = 'nova-2';
    case Nova = 'nova';

    static function toArray(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
