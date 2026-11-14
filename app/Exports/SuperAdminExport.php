<?php

namespace App\Exports;

use App\Models\User;
use App\Scopes\BranchScope;

/**
 * Exporta únicamente los usuarios superadmin (sin restaurante asignado).
 */
class SuperAdminExport extends StaffExport
{
    public function collection()
    {
        return User::withoutGlobalScope(BranchScope::class)
            ->whereNull('restaurant_id')
            ->with('roles')
            ->get();
    }
}
