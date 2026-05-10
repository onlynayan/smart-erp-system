<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Toyota',
            'Honda',
            'Nissan',
            'Bosch',
            'Denso',
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['name' => $brand],
                ['name' => $brand]
            );
        }
    }
}
