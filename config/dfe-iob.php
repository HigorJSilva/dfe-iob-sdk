<?php

return [
    /*
    |--------------------------------------------------------------------------
    | URLs base dos servidores IOB
    |--------------------------------------------------------------------------
    | Em produção, remova o prefixo "dev-" das URLs.
    */
    'usm_base_url' => env('IOB_USM_BASE_URL', 'https://development-usm-svc-app.iob.com.br'),
    'dfe_nfe_base_url' => env('IOB_DFE_NFE_BASE_URL', 'https://dev-dfe.nfe.iob.com.br'),
    'dfe_nfse_base_url' => env('IOB_DFE_NFSE_BASE_URL', 'https://dev-dfe-nfse.iob.com.br'),
    'dfe_cte_base_url' => env('IOB_DFE_CTE_BASE_URL', 'https://dev-dfe-cte.iob.com.br'),
    'adm_base_url' => env('IOB_ADM_BASE_URL', 'https://dev-dfe-adm.iob.com.br'),

    /*
    |--------------------------------------------------------------------------
    | Autenticação — Módulos DFe (NFC-e, NF-e, NFS-e, CT-e)
    |--------------------------------------------------------------------------
    | Utiliza x-api-key simples nos headers.
    */
    'api_key' => env('IOB_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Autenticação — Módulo ADM (Aplicação, Empresa, Certificado)
    |--------------------------------------------------------------------------
    | O módulo ADM usa OAuth em 3 etapas via servidor USM.
    |
    | client_id e client_secret: credenciais do cadastro USM
    | x_api_key:                 x-api-key do USM cadastrado na IOB
    | username e password:       dados do cadastro na aplicação do motor IOB
    */
    'adm' => [
        'client_id'     => env('IOB_ADM_CLIENT_ID'),
        'client_secret' => env('IOB_ADM_CLIENT_SECRET'),
        'x_api_key'     => env('IOB_ADM_X_API_KEY'),
        'username'      => env('IOB_ADM_USERNAME'),
        'password'      => env('IOB_ADM_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache de tokens ADM
    |--------------------------------------------------------------------------
    | Prefixo e TTL (em segundos) usados para armazenar tokens no cache Laravel.
    | O access_token expira em 3600 s (1h) e o refresh_token em 432000 s (5d).
    */
    'token_cache' => [
        'prefix' => env('IOB_TOKEN_CACHE_PREFIX', 'iob_adm_token'),
        'store'  => env('IOB_TOKEN_CACHE_STORE', null), // null = cache padrão do Laravel
    ],

    /*
    |--------------------------------------------------------------------------
    | Opções de HTTP
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout'         => env('IOB_HTTP_TIMEOUT', 30),
        'connect_timeout' => env('IOB_HTTP_CONNECT_TIMEOUT', 10),
    ],
];
