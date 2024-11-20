<?php

namespace Kimbocare\Ai\DeepGram\Enums;

enum DeepGramContentTypeEnum: string
{
    case WAV = 'audio/wav';
    case JSON = 'application/json';

    static function toArray(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
