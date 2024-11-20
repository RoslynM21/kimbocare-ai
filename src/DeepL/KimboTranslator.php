<?php

namespace Kimbocare\Ai\DeepL;

use DeepL\Translator;
use Exception;
use Kimbocare\Ai\DeepL\Enums\DeepLLanguageEnum;

class KimboTranslator
{
    private $translator;
    public function __construct($apiKey)
    {
        $this->translator = new Translator($apiKey);
    }

    public function translate(string $text, DeepLLanguageEnum $targetLang, ?DeepLLanguageEnum $sourceLang = null)
    {
        try {
            $sLang = $this->getLanguage($sourceLang);
            $tLang = $this->getLanguage($targetLang);
            return $this->translator->translateText($text, $sLang, $tLang);
        } catch (Exception $e) {
            // journalisation des erreurs
            return $e;
        }
    }

    public function translateInAllLanguage(string $text): array
    {
        $translations = [];
        foreach (DeepLLanguageEnum::cases() as $language) {
            $translations[$language->value] = $this->translate($text, $language)->text;
        }
        return $translations;
    }

    private function getLanguage(DeepLLanguageEnum $language = null): ?string
    {
        if (isset($language)) {
            foreach (DeepLLanguageEnum::cases() as $lang) {
                if ($language->value === $lang->value) {
                    return $lang->value;
                }
            }
        }

        return null;
    }
}
