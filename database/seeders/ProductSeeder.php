<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Schema::hasTable('users')) {
            Product::truncate();
        }

        $data = [];
        for ($i = 0; $i < 2000000; $i++) {
            $data[] = [
                'name' => fake()->name(),
                'detail' => fake()->text(),
            ];

            if ($i % 1000 == 0) {
                Product::insert($data);
                $data = [];
            }
        }

        Product::insert($data);
    }
}
