<?php

return [

    /*
    |--------------------------------------------------------------------------
    | NIT de la empresa activa
    |--------------------------------------------------------------------------
    |
    | En instalaciones Single Tenant, define aquí el NIT de la empresa
    | configurada en esta instancia. Si se deja vacío, el sistema tomará
    | el primer registro de la tabla companies por fecha de creación.
    |
    | Ejemplo: CONTPASS_COMPANY_NIT=900123456
    |
    */
    'company_nit' => env('CONTPASS_COMPANY_NIT'),

    /*
    |--------------------------------------------------------------------------
    | Conciliación bancaria — motor de cruce
    |--------------------------------------------------------------------------
    |
    | match_window_days: ventana de fecha (± días) alrededor de la fecha de
    | una línea de extracto dentro de la cual se buscan Payment candidatos.
    |
    | match_pool_limit: límite superior explícito del número de Payment
    | candidatos ANTES de generar combinaciones de lote (2 a 5) — ver
    | 03-RESEARCH.md Open Question #3. Si el pool de candidatos de una línea
    | excede este valor, esa línea solo recibe sugerencias 1:1, nunca de
    | lote, para evitar explosión combinatoria.
    |
    */
    'bank_reconciliation' => [
        'match_window_days' => env('BANKREC_MATCH_WINDOW_DAYS', 3),
        'match_pool_limit' => env('BANKREC_MATCH_POOL_LIMIT', 10),
    ],

];
