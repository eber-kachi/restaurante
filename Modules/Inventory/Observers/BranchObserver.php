<?php

namespace Modules\Inventory\Observers;

use App\Models\Branch;
use Modules\Inventory\Entities\Unit;
use Modules\Inventory\Entities\InventoryItemCategory;

class BranchObserver
{

    public function created(Branch $branch): void
    {

        foreach (Unit::defaults() as $unit) {
            Unit::firstOrCreate(array_merge($unit, [
                'branch_id' => $branch->id
            ]));
        }

        foreach (InventoryItemCategory::defaults() as $category) {
            InventoryItemCategory::firstOrCreate([
                'branch_id' => $branch->id,
                'name' => $category
            ]);
        }
    }
}
