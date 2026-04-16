<?php

namespace Database\Seeders;

use App\Models\AtkItem;
use App\Models\AtkStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AtkItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Pulpen Hitam', 'unit' => 'pcs', 'minimum_stock' => 10, 'stock' => 100],
            ['name' => 'Pulpen Biru', 'unit' => 'pcs', 'minimum_stock' => 10, 'stock' => 100],
            ['name' => 'Pensil 2B', 'unit' => 'pcs', 'minimum_stock' => 10, 'stock' => 50],
            ['name' => 'Penghapus', 'unit' => 'pcs', 'minimum_stock' => 10, 'stock' => 40],
            ['name' => 'Penggaris 30cm', 'unit' => 'pcs', 'minimum_stock' => 5, 'stock' => 20],
            ['name' => 'Buku Tulis', 'unit' => 'pcs', 'minimum_stock' => 10, 'stock' => 80],
            ['name' => 'Kertas A4', 'unit' => 'rim', 'minimum_stock' => 5, 'stock' => 25],
            ['name' => 'Map Plastik', 'unit' => 'pcs', 'minimum_stock' => 10, 'stock' => 60],
            ['name' => 'Sticky Note', 'unit' => 'pack', 'minimum_stock' => 5, 'stock' => 20],
            ['name' => 'Spidol Whiteboard', 'unit' => 'pcs', 'minimum_stock' => 5, 'stock' => 24],
            ['name' => 'Tinta Printer', 'unit' => 'pcs', 'minimum_stock' => 2, 'stock' => 8],
            ['name' => 'Stapler', 'unit' => 'pcs', 'minimum_stock' => 2, 'stock' => 10],
            ['name' => 'Isi Staples', 'unit' => 'box', 'minimum_stock' => 5, 'stock' => 25],
            ['name' => 'Gunting', 'unit' => 'pcs', 'minimum_stock' => 3, 'stock' => 12],
            ['name' => 'Lakban', 'unit' => 'roll', 'minimum_stock' => 5, 'stock' => 18],
        ];

        foreach ($items as $index => $row) {
            $item = AtkItem::updateOrCreate(
                ['name' => $row['name']],
                [
                    'slug' => Str::slug($row['name']),
                    'code' => 'ATK-' . str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                    'unit' => $row['unit'],
                    'description' => null,
                    'minimum_stock' => $row['minimum_stock'],
                    'is_active' => true,
                ]
            );

            AtkStock::updateOrCreate(
                ['atk_item_id' => $item->id],
                [
                    'current_stock' => $row['stock'],
                    'reserved_stock' => 0,
                    'location' => 'Gudang Utama',
                    'notes' => 'Seeder awal ATK',
                ]
            );
        }
    }
}