<?php

namespace App\Imports;

use Illuminate\Support\Str;

/**
 * Normaliza las cabeceras del archivo de clientes (español o inglés)
 * a las claves internas que usa CustomerImport: name, phone, email.
 *
 * Con WithHeadingRow, Maatwebsite convierte cada cabecera a slug
 * (p. ej. "Teléfono" => "telefono", "Correo electrónico" => "correo_electronico").
 */
class CustomerImportHeadings
{
    /** clave interna => alias aceptados (ya en formato slug) */
    protected const ALIASES = [
        'name' => ['name', 'nombre', 'nombre_completo', 'cliente'],
        'phone' => ['phone', 'telefono', 'celular', 'movil', 'numero_de_telefono'],
        'email' => ['email', 'correo', 'correo_electronico', 'e_mail'],
    ];

    public static function normalize(array $row): array
    {
        $normalized = [];

        foreach ($row as $heading => $value) {
            $slug = Str::slug((string) $heading, '_');
            $key = self::resolve($slug) ?? $heading;

            // No sobrescribir un valor ya encontrado con una columna vacía
            if (!array_key_exists($key, $normalized) || ($normalized[$key] === null || $normalized[$key] === '')) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    protected static function resolve(string $slug): ?string
    {
        foreach (self::ALIASES as $key => $aliases) {
            if (in_array($slug, $aliases, true)) {
                return $key;
            }
        }

        return null;
    }
}
