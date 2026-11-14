<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasBranch;
// use Modules\Inventory\Database\Factories\UnitFactory;

class Unit extends Model
{
    use HasFactory;
    use HasBranch;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    /**
     * The units that are available.
     */
    const UNITS = [
        ['name' => 'Kilogram', 'symbol' => 'kg'],
        ['name' => 'Gram', 'symbol' => 'g'],
        ['name' => 'Liter', 'symbol' => 'L'],
        ['name' => 'Milliliter', 'symbol' => 'ml'],
        ['name' => 'Piece', 'symbol' => 'pc'],
        ['name' => 'Box', 'symbol' => 'box'],
        ['name' => 'Dozen', 'symbol' => 'dz'],
        ['name' => 'Bottle', 'symbol' => 'btl'],
        ['name' => 'Package', 'symbol' => 'pkg'],
        ['name' => 'Can', 'symbol' => 'can'],
    ];

    /**
     * Unidades predeterminadas con el nombre traducido al idioma activo.
     * UNITS conserva los nombres en inglés como identificador estable
     * (p. ej. para la migración que traduce los datos existentes).
     */
    public static function defaults(): array
    {
        return array_map(fn ($unit) => [
            'name' => __('inventory::modules.defaultUnits.' . strtolower($unit['name'])),
            'symbol' => $unit['symbol'],
        ], self::UNITS);
    }
}
