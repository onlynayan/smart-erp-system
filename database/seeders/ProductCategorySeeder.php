<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $parent = ProductCategory::updateOrCreate(
            ['slug' => 'vehicle-parts'],
            [
                'parent_id' => null,
                'name' => 'Vehicle Parts',
            ]
        );

        $categories = [
            'Engine Parts',
            'Brake Parts',
            'Suspension Parts',
            'Electrical Parts',
        ];

        foreach ($categories as $category) {
            ProductCategory::updateOrCreate(
                ['slug' => Str::slug($category)],
                [
                    'parent_id' => $parent->id,
                    'name' => $category,
                ]
            );
        }
    }
}
