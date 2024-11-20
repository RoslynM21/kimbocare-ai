<?php

namespace Kimbocare\Ai\Scrapper\Models;

class PlaceCoordinate
{
    public float $latitude;
    public float $longitude;
    public ?float $radius;

    public function __construct(float $latitude, float $longitude, ?float $radius = null)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->radius = $radius;
    }
}
