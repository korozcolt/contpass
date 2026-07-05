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

];
