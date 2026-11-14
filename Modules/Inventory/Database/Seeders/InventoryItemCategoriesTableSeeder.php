<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Entities\InventoryItemCategory;

class InventoryItemCategoriesTableSeeder extends Seeder
{
    public function run(): void
    {

        foreach (InventoryItemCategory::defaults() as $category) {
            InventoryItemCategory::firstOrCreate([
                'branch_id' => 1,
                'name' => $category
            ]);
        }
    }
}
