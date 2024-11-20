<?php

namespace Kimbocare\Ai\Scrapper\Enums;

enum ScrapperMaskEnum: string
{
    case ALL =  'places.*';
    case HcpMask = 'places.displayName,places.regularOpeningHours,places.formattedAddress,places.websiteUri,places.internationalPhoneNumber,places.location,places.rating,places.googleMapsUri,places.primaryType,places.shortFormattedAddress,places.photos';

    static function toArray(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
