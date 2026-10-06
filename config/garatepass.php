<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Token de API
    |--------------------------------------------------------------------------
    |
    | La app GaratePass se autentica con un token compartido, idéntico en
    | pruebas y producción. El token identifica a la aplicación, no a una
    | persona: cada endpoint resuelve la identidad de quien opera a partir
    | del código QR escaneado.
    |
    */

    'token' => env('GARATEPASS_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Ventana de vales
    |--------------------------------------------------------------------------
    |
    | Ventana de enfriamiento por trabajador y tiempo de retención de las
    | claves de idempotencia. Esta última cubre un turno completo, de modo
    | que un lote emitido ayer nunca pueda re-emitirse hoy por accidente.
    |
    */

    'ventana_horas' => (int) env('GARATEPASS_VENTANA_HORAS', 4),

    'idempotency_ttl_horas' => (int) env('GARATEPASS_IDEMPOTENCY_TTL_HORAS', 24),

    /*
    |--------------------------------------------------------------------------
    | Correlativos
    |--------------------------------------------------------------------------
    */

    'prefijo_lote' => env('GARATEPASS_PREFIJO_LOTE', 'LOT-'),

    'lote_maximo' => (int) env('GARATEPASS_LOTE_MAXIMO', 500),

    /*
    |--------------------------------------------------------------------------
    | Vale sin centro de costo
    |--------------------------------------------------------------------------
    |
    | Muchos trabajadores no tienen centro de costo asignado. Frenar la
    | impresión por un dato administrativo pendiente convertiría un problema
    | de carga de datos en un problema de servicio, así que el vale se
    | imprime igual con este texto en su lugar.
    |
    */

    'texto_sin_centro_costo' => env('GARATEPASS_TEXTO_SIN_CENTRO_COSTO', '(sin centro de costo)'),

];
