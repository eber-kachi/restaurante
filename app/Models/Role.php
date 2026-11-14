<?php

namespace App\Models;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasRestaurant;

    protected $fillable = [
        'name',
        'guard_name',
        'restaurant_id',
        'display_name'
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Nombre del rol para mostrar en pantalla.
     *
     * `display_name` se guarda SIEMPRE en inglés porque el código lo compara
     * (p. ej. 'Waiter', 'Admin', 'Branch Head' en RoleSettings, CashRegister y vistas).
     * Para mostrarlo traducido usar `$role->translated_name`; los roles personalizados
     * que no tienen traducción se muestran tal cual.
     */
    protected function translatedName(): Attribute
    {
        return Attribute::get(function () {
            $key = 'modules.staff.' . $this->display_name;

            return Lang::has($key) ? __($key) : $this->display_name;
        });
    }
}
