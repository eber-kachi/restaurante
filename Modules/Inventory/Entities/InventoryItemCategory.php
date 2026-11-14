<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasBranch;

class InventoryItemCategory extends Model
{
    use HasFactory;
    use HasBranch;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    const CATEGORIES = [
        'Meat & Poultry',
        'Seafood',
        'Dairy & Eggs',
        'Fresh Produce',
        'Herbs & Spices',
        'Dry Goods',
        'Canned Goods',
        'Beverages',
        'Condiments & Sauces',
        'Baking Supplies',
        'Oils & Vinegars',
        'Frozen Foods',
        'Cleaning Supplies',
        'Kitchen Equipment',
        'Disposables'
    ];

    /** Claves de traducción, en el mismo orden que CATEGORIES. */
    const CATEGORY_KEYS = [
        'meat_poultry', 'seafood', 'dairy_eggs', 'fresh_produce', 'herbs_spices',
        'dry_goods', 'canned_goods', 'beverages', 'condiments_sauces', 'baking_supplies',
        'oils_vinegars', 'frozen_foods', 'cleaning_supplies', 'kitchen_equipment', 'disposables',
    ];

    /**
     * Categorías predeterminadas con el nombre traducido al idioma activo.
     * CATEGORIES conserva los nombres en inglés como identificador estable.
     */
    public static function defaults(): array
    {
        return array_map(
            fn ($key) => __('inventory::modules.defaultItemCategories.' . $key),
            self::CATEGORY_KEYS
        );
    }
}
