<?php

namespace Kimbocare\Ai\OpenAi\Models;

class GptResponse
{
    public mixed $data;
    public bool $isPormpt;

    public function __construct(mixed $data, bool $isPormpt)
    {
        $this->data = $data;
        $this->isPormpt = $isPormpt;
    }
}
