<?php

namespace Kimbocare\Ai\DeepGram;

use Exception;
use Illuminate\Http\UploadedFile;
use Kimbocare\Ai\DeepGram\Enums\DeepGramContentTypeEnum;
use Kimbocare\Ai\DeepGram\Enums\DeepGramModelEnum;
use Kimbocare\Ai\DeepGram\Models\DeepGramResponse;

class KimboSpeechToText
{
    private DeepGramModelEnum $model;
    private $apiKey;
    private $contentType;
    public function __construct(string $apiKey, ?DeepGramContentTypeEnum $contentType = DeepGramContentTypeEnum::WAV, ?DeepGramModelEnum $model = DeepGramModelEnum::Nova2)
    {
        $this->model = $model;
        $this->apiKey = $apiKey;
        $this->contentType = $contentType;
    }

    public function convert(UploadedFile $file)
    {
        try {
            $model = $this->model->value;
            $contentType = $this->contentType->value;
            $url = "https://api.deepgram.com/v1/listen?model=$model&detect_language=true";

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $file->get());
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Token " . $this->apiKey,
                "Content-Type: $contentType"
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            curl_close($ch);
            if ($response === false) {
                return null;
            } elseif (!isset(json_decode($response)->results)) {
                return 400;
            } else {
                return new DeepGramResponse(json_decode($response)->results);
            }
        } catch (Exception $e) {
            // journalisation des erreurs
            throw $e;
        }
    }

    public function getText(DeepGramResponse $response)
    {
        return $response->data?->channels[0]?->alternatives[0]?->transcript;
    }
}
