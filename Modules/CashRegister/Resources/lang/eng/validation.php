<?php

return [
    // Mensajes de validación de denominación
    'denomination' => [
        'name' => [
            'required' => 'El nombre de la denominación es obligatorio.',
            'max' => 'El nombre de la denominación no puede tener más de :max caracteres.',
        ],
        'value' => [
            'required' => 'El valor de la denominación es obligatorio.',
            'numeric' => 'El valor de la denominación debe ser un número.',
            'min' => 'El valor de la denominación debe ser al menos :min.',
            'max' => 'El valor de la denominación no puede ser mayor que :max.',
        ],
        'type' => [
            'required' => 'El tipo de denominación es obligatorio.',
            'in' => 'El tipo de denominación seleccionado no es válido.',
        ],
        // currency eliminado
        'description' => [
            'max' => 'La descripción de la denominación no puede tener más de :max caracteres.',
        ],
    ],
];
