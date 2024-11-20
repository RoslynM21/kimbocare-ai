<?php

namespace Kimbocare\Ai\Scrapper\Models;

class PlacesApiResponse
{
    public ?array $places;

    public function __construct(?array $places)
    {
        $this->places = $places;
    }
}
