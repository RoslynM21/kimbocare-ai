<?php

namespace Kimbocare\Ai\DeepL\Enums;

enum DeepLLanguageEnum: string
{
    case French = 'fr';
    case English = 'en-US';

    static function toArray(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
