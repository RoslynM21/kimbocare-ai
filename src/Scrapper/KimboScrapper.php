<?php

namespace Kimbocare\Ai\Scrapper;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Kimbocare\Ai\Scrapper\Enums\ScrapperMaskEnum;
use Kimbocare\Ai\Scrapper\Models\PlaceCoordinate;
use Kimbocare\Ai\Scrapper\Models\PlacesApiResponse;
use stdClass;

class KimboScrapper
{
    private string $key;

    public string $url = 'https://places.googleapis.com/v1/places:searchText';

    public string $geocodeUrl = 'https://maps.googleapis.com/maps/api/geocode/json';

    public string $nearbySearchUrl = "https://places.googleapis.com/v1/places:searchNearby";

    private Client $client;

    private array $headers;

    public function __construct($apiKey, ScrapperMaskEnum $fieldMask = ScrapperMaskEnum::ALL)
    {
        $this->client = new Client();

        $this->key = $apiKey;

        $this->headers = [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => $fieldMask->value
        ];
    }

    public function getPlaces(string $formattedAddress, ?string $type = null): PlacesApiResponse
    {
        $data = [
            'headers' => $this->headers,
            'json' => [
                'textQuery' => $formattedAddress,
            ]
        ];
        if (isset($type)) {
            $data[] = ['includedType' => $type];
        }

        $response = $this->client->post($this->url, $data);

        return new PlacesApiResponse(json_decode($response->getBody()->getContents())?->places);
    }

    public function geolocatePlacesWithRadius(PlaceCoordinate $coordinate, array $types = []): PlacesApiResponse
    {
        $datas = [
            "includedTypes" => $types,
            "locationRestriction" => [
                "circle" => [
                    "center" => [
                        "latitude" => $coordinate->latitude,
                        "longitude" => $coordinate->longitude
                    ],
                    "radius" => $coordinate->radius
                ]
            ]
        ];

        try {
            $response = $this->client->post($this->nearbySearchUrl, [
                'headers' => $this->headers,
                'json' => $datas
            ]);

            return new PlacesApiResponse(json_decode($response->getBody()->getContents())?->places);
        } catch (\Exception $e) {
            // logger les erreurs
        }
    }

    public function buildPlacesCollection(PlacesApiResponse $response): Collection
    {
        $hcps = new Collection();
        foreach ($response->places as $place) {
            $hcps->push($this->parseResponse($place));
        }

        return $hcps->filter(function ($hcp) {
            return preg_match('/^[a-zA-ZÀ-ÿ\s]+$/u', $hcp->displayName);
        })->sortByDesc('rating');
    }

    public function geocodePlace($address): PlaceCoordinate
    {
        $response = $this->client->get($this->geocodeUrl . "?address=$address&key=$this->key");

        $body = json_decode($response->getBody()->getContents())->results[0];
        $mainLocation = $body->geometry->location;
        $coordinate = new PlaceCoordinate($mainLocation->lat, $mainLocation->lng);

        $northeastLocation = $body->geometry->bounds->northeast;

        $coordinate->radius = $this->calculateRadius($coordinate, new PlaceCoordinate($northeastLocation->lat, $northeastLocation->lng));

        return $coordinate;
    }

    public function calculateRadius(PlaceCoordinate $mainLocation, PlaceCoordinate $northeastLocation)
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($mainLocation->latitude);
        $lngFrom = deg2rad($mainLocation->longitude);
        $latTo = deg2rad($northeastLocation->latitude);
        $lngTo = deg2rad($northeastLocation->longitude);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function parseResponse($places)
    {
        $response = new stdClass();
        $response->displayName = $places?->displayName?->text ?? null;
        $response->primaryType = $places?->primaryType ?? null;
        $response->googleMapsUri = $places?->googleMapsUri ?? null;
        $response->shortFormattedAddress = $places?->shortFormattedAddress ?? null;
        $response->photos = $places?->photos ?? null;
        $response->adress = $places?->formattedAddress ?? null;
        $response->longitude = $places?->location?->longitude ?? null;
        $response->latitude = $places?->location?->latitude ?? null;
        $response->rating = $places?->rating ?? null;
        $response->website = $places?->websiteUri ?? null;
        $response->tel = $places->internationalPhoneNumber ?? null;
        $response->mondayHour = $places?->regularOpeningHours?->weekdayDescriptions[0] ?? "Monday: 8:00 AM - 10:00 PM";
        $response->tuesdayHour = $places?->regularOpeningHours?->weekdayDescriptions[1] ?? "Tuesday: 8:00 AM - 10:00 PM";
        $response->wednesdayHour = $places?->regularOpeningHours?->weekdayDescriptions[2] ?? "Wednesday: 8:00 AM - 10:00 PM";
        $response->thursdayHour = $places?->regularOpeningHours?->weekdayDescriptions[3] ?? "Thursday: 8:00 AM - 10:00 PM";
        $response->fridayHour = $places?->regularOpeningHours?->weekdayDescriptions[4] ?? "Friday: 8:00 AM - 10:00 PM";
        $response->saturdayHour = $places?->regularOpeningHours?->weekdayDescriptions[5] ?? "Saturday: 8:00 AM - 10:00 PsM";
        $response->sundayHour = $places?->regularOpeningHours?->weekdayDescriptions[6] ?? "Sunday: 8:00 AM - 10:00 PM";

        return $response;
    }
}
