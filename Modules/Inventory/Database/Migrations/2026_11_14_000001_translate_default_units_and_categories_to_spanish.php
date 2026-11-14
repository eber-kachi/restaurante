<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Traduce al español las unidades y categorías de inventario creadas por defecto
 * (Unit::UNITS e InventoryItemCategory::CATEGORIES) que ya existen en la base de datos.
 *
 * - Solo se renombran las filas que todavía tienen el nombre original en inglés
 *   (y, en las unidades, el mismo símbolo que el predeterminado), así que no se tocan
 *   las unidades o categorías que el usuario creó o editó.
 * - Si en una sucursal ya existe una fila con el nombre en español, no se renombra
 *   para no crear duplicados.
 * - Los símbolos (kg, g, L, ml, pc...) no se modifican: algunos procesos (p. ej. el
 *   seeder de recetas) los usan como identificador.
 */
return new class extends Migration
{
    /** [nombre en inglés, símbolo, nombre en español] */
    private const UNITS = [
        ['Kilogram', 'kg', 'Kilogramo'],
        ['Gram', 'g', 'Gramo'],
        ['Liter', 'L', 'Litro'],
        ['Milliliter', 'ml', 'Mililitro'],
        ['Piece', 'pc', 'Pieza'],
        ['Box', 'box', 'Caja'],
        ['Dozen', 'dz', 'Docena'],
        ['Bottle', 'btl', 'Botella'],
        ['Package', 'pkg', 'Paquete'],
        ['Can', 'can', 'Lata'],
    ];

    /** nombre en inglés => nombre en español */
    private const CATEGORIES = [
        'Meat & Poultry' => 'Carnes y aves',
        'Seafood' => 'Mariscos',
        'Dairy & Eggs' => 'Lácteos y huevos',
        'Fresh Produce' => 'Productos frescos',
        'Herbs & Spices' => 'Hierbas y especias',
        'Dry Goods' => 'Productos secos',
        'Canned Goods' => 'Conservas',
        'Beverages' => 'Bebidas',
        'Condiments & Sauces' => 'Condimentos y salsas',
        'Baking Supplies' => 'Insumos de repostería',
        'Oils & Vinegars' => 'Aceites y vinagres',
        'Frozen Foods' => 'Alimentos congelados',
        'Cleaning Supplies' => 'Artículos de limpieza',
        'Kitchen Equipment' => 'Equipo de cocina',
        'Disposables' => 'Desechables',
    ];

    public function up(): void
    {
        if (Schema::hasTable('units')) {
            foreach (self::UNITS as [$en, $symbol, $es]) {
                $this->rename('units', $en, $es, $symbol);
            }
        }

        if (Schema::hasTable('inventory_item_categories')) {
            foreach (self::CATEGORIES as $en => $es) {
                $this->rename('inventory_item_categories', $en, $es);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('units')) {
            foreach (self::UNITS as [$en, $symbol, $es]) {
                $this->rename('units', $es, $en, $symbol);
            }
        }

        if (Schema::hasTable('inventory_item_categories')) {
            foreach (self::CATEGORIES as $en => $es) {
                $this->rename('inventory_item_categories', $es, $en);
            }
        }
    }

    /**
     * Renombra las filas de $table de $from a $to, sucursal por sucursal,
     * omitiendo las sucursales donde ya existe una fila llamada $to.
     */
    private function rename(string $table, string $from, string $to, ?string $symbol = null): void
    {
        $query = DB::table($table)->where('name', $from);

        if ($symbol !== null) {
            $query->where('symbol', $symbol);
        }

        $rows = $query->get(['id', 'branch_id']);

        foreach ($rows as $row) {
            $alreadyExists = DB::table($table)
                ->where('branch_id', $row->branch_id)
                ->where('name', $to)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            DB::table($table)->where('id', $row->id)->update(['name' => $to, 'updated_at' => now()]);
        }
    }
};
