<?php

namespace Kimbocare\Ai\OpenAi;

use Exception;
use Kimbocare\Ai\OpenAi\Enums\OpenAiModelEnum;
use Kimbocare\Ai\OpenAi\Models\GptResponse;
use OpenAI;
use OpenAI\Client;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;

class KimboOpenAi
{
    private Client $client;
    public ?string $assistantId;

    public function __construct($apiKey, ?string $assistantId = null)
    {
        $this->assistantId = $assistantId;
        $this->client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withOrganization(null)
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 120]))
            ->make();
    }

    public function executePromt(string $prompt, OpenAiModelEnum $model =  OpenAiModelEnum::GPT_4O_mini, array $imagePaths = []): GptResponse
    {
        $contents = [
            ["type" => "text", "text" => $prompt]
        ];

        foreach ($imagePaths as $path) {
            $contents[] = [
                "type" => "image_url",
                "image_url" => [
                    "url" => "data:image/png;base64," . base64_encode(file_get_contents($path))
                ]
            ];
        }

        try {
            $response = $this->client->chat()->create([
                'model' => $model->value,
                'messages' => [
                    ["role" => "user", "content" => $contents]
                ]
            ]);

            return new GptResponse($response ?? null, true);
        } catch (Exception $exception) {
            // journalisation des erreurs
            return null;
        }
    }

    public function executeAssistant(?string $moreDescription = '', ?string $fullPathFile = null): GptResponse
    {
        if (!isset($this->assistantId)) {
            return null;
        }
        $openAiFile = null;
        $message = [
            [
                'role' => 'user',
                'content' => $moreDescription,
            ]
        ];

        if (isset($fullPathFile)) {
            $openAiFile = $this->client->files()->upload([
                'purpose' => 'assistants',
                'file' => fopen($fullPathFile, 'r'),
            ]);

            $message[0]['attachments'] = [
                [
                    'file_id' => $openAiFile->id,
                    'tools' => [['type' => 'file_search']]
                ]
            ];
        }

        $response = $this->client->threads()->createAndRun([
            'assistant_id' => $this->assistantId,
            'thread' => [
                'messages' => $message
            ],
        ]);

        return $this->retrive($response, $openAiFile);
    }

    public function getText(GptResponse $response)
    {
        if ($response->isPormpt) {
            return $this->decodeGptResponse($response->data["choices"][0]['message']['content'] ?? null);
        } else {
            return $this->decodeGptResponse($response->data->content[0]->text->value ?? null);
        }
    }

    private function retrive(ThreadRunResponse $threadRun, $openAiFile = null): GptResponse
    {
        while (in_array($threadRun->status, ['queued', 'in_progress'])) {
            $threadRun = $this->client->threads()->runs()->retrieve(
                threadId: $threadRun->threadId,
                runId: $threadRun->id,
            );
        }
        if ($threadRun->status !== 'completed') {
            return 400;
        }

        $messageList = $this->client->threads()->messages()->list(
            threadId: $threadRun->threadId,
        );

        if (isset($openAiFile)) {
            $this->client->files()->delete($openAiFile->id);
        }

        return new GptResponse($messageList->data[0], false);
    }

    private function decodeGptResponse(?string $data)
    {
        $res = str_replace('```', '', $data);
        $res = str_replace('json', '', $res);
        $res = str_replace('\\n', '', $res);
        $res = json_decode($res);
        return isset($res) ? $res : $data;
    }
}
