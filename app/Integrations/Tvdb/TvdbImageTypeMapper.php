<?php

namespace App\Integrations\Tvdb;

class TvdbImageTypeMapper
{
    protected $types = [
        1 => 'banners',
        2 => 'posters',
        3 => 'backgrounds',
        5 => 'icons',
        6 => 'banners',
        7 => 'posters',
        8 => 'backgrounds',
        10 => 'icons',
        11 => 'screencap',
        12 => 'screencap',
        13 => 'photo',
        14 => 'posters',
        15 => 'backgrounds',
        16 => 'banners',
        18 => 'icons',
        19 => 'icons',
        20 => 'cinemagraphs',
        21 => 'cinemagraphs',
        22 => 'clearart',
        23 => 'clearlogo',
        24 => 'clearart',
        25 => 'clearlogo',
        26 => 'icons',
        27 => 'posters',
    ];

    public function map(string $type): string
    {
        $filteredTypeKeys = array_keys($this->types, $type);
        return implode(',', $filteredTypeKeys);
    }
}
