<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'open_meteo' => [
        'marine_url' => env('OPEN_METEO_MARINE_URL', 'https://marine-api.open-meteo.com/v1/marine'),
        'weather_url' => env('OPEN_METEO_WEATHER_URL', 'https://api.open-meteo.com/v1/forecast'),
        'timeout' => (int) env('OPEN_METEO_TIMEOUT', 8),
    ],

    'searoute' => [
        'python' => env('SEAROUTE_PYTHON', 'python'),
    ],

    'emodnet' => [
        'url' => env('EMODNET_REST_URL', 'https://rest.emodnet-bathymetry.eu'),
        'timeout' => (int) env('EMODNET_TIMEOUT', 12),
    ],

    'overpass' => [
        'url' => env('OVERPASS_URL', 'https://overpass-api.de/api/interpreter'),
        'user_agent' => env('OVERPASS_USER_AGENT', 'MariWaze/1.0 (contact@mariwaze.test)'),
        'timeout' => (int) env('OVERPASS_TIMEOUT', 180),
    ],

    'worldtides' => [
        'url' => env('WORLDTIDES_URL', 'https://www.worldtides.info/api/v3'),
        'key' => env('WORLDTIDES_API_KEY'),
        'timeout' => (int) env('WORLDTIDES_TIMEOUT', 8),
    ],

    'geocoding' => [
        'mapbox_url' => env('MAPBOX_GEOCODING_URL', 'https://api.mapbox.com/search/geocode/v6/forward'),
        'mapbox_token' => env('MAPBOX_ACCESS_TOKEN'),
        'mapbox_permanent' => env('MAPBOX_GEOCODING_PERMANENT', true),
        'nominatim_url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org/search'),
        'user_agent' => env('GEOCODING_USER_AGENT', 'MariWaze/1.0 (contact@mariwaze.test)'),
        'timeout' => (int) env('GEOCODING_TIMEOUT', 6),
    ],

];
