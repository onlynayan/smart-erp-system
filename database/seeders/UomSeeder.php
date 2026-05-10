<?php

namespace Database\Seeders;

use App\Models\Uom;
use Illuminate\Database\Seeder;

class UomSeeder extends Seeder
{
    public function run(): void
    {
        $uoms = [
            ['name' => 'Pieces', 'short_name' => 'pcs'],
            ['name' => 'Box', 'short_name' => 'box'],
            ['name' => 'Set', 'short_name' => 'set'],
            ['name' => 'Liter', 'short_name' => 'ltr'],
        ];

        foreach ($uoms as $uom) {
            Uom::updateOrCreate(
                ['short_name' => $uom['short_name']],
                $uom
            );
        }
    }
}
