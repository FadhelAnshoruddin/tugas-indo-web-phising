<?php

namespace Database\Seeders;

use App\Models\Prize;
use Illuminate\Database\Seeder;

class PrizeSeeder extends Seeder
{
    public function run(): void
    {
        Prize::query()->delete();

        $prizes = [
            ['name' => 'Rp.10.000.000', 'color' => '#ef8354', 'weight' => 10],
            ['name' => 'Rp.25.000.000', 'color' => '#f4d35e', 'weight' => 20],
            ['name' => 'Rp.50.000.000', 'color' => '#72bda3', 'weight' => 25],
            ['name' => 'Rp.100.000.000', 'color' => '#9aa5b1', 'weight' => 20],
            ['name' => 'Rp.5.000.000', 'color' => '#4ea5d9', 'weight' => 15],
            ['name' => 'Hadiah Utama', 'color' => '#c97b84', 'weight' => 2],
            ['name' => 'Rp.1.000.000', 'color' => '#d99ac5', 'weight' => 25],
            ['name' => 'Zonk', 'color' => '#657786', 'weight' => 15],
        ];

        Prize::insert($prizes);
    }
}