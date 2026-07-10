<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credenciais da API do IXC Soft
    |--------------------------------------------------------------------------
    */
    'base_url' => env('IXC_BASE_URL'),
    'user_id' => env('IXC_USER_ID'),
    'token' => env('IXC_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Cache de respostas
    |--------------------------------------------------------------------------
    |
    | Quando habilitado, envolve o HttpClientInterface com
    | RedRodrigo\IxcSdk\Http\CachingHttpClient, usando o cache store do
    | Laravel indicado em "store" (null = store padrão da aplicação).
    |
    */
    'cache' => [
        'enabled' => (bool) env('IXC_CACHE_ENABLED', false),
        'store' => env('IXC_CACHE_STORE'),
        'ttl' => (int) env('IXC_CACHE_TTL', 300),
    ],
];
