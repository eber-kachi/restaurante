<?php

$addOnOf = 'tabletrack';

return [
    'name' => 'CashRegister',
    'verification_required' => true,
    'envato_item_id' => 60317674,
    'parent_envato_id' => 55116396, // TableTrack Envato ID
    'parent_min_version' => '1.2.48',
    'script_name' => $addOnOf . '-cashregister-module',
    'parent_product_name' => $addOnOf,
    'setting' => \Modules\CashRegister\Entities\CashRegisterGlobalSetting::class,
    // Depósito en caja fuerte (safe drop): oculto por defecto porque no se usa en Bolivia.
    // Para volver a mostrarlo en pantallas, informes e impresiones: CASHREGISTER_SAFE_DROP=true en .env
    'safe_drop_enabled' => env('CASHREGISTER_SAFE_DROP', false),
];
