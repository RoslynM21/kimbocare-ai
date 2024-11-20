<?php

namespace Kimbocare\Ai\DeepGram\Models;

class DeepGramResponse
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
}
