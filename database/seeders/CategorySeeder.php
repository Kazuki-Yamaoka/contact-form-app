<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Category::factory()
            ->count(5)
            ->sequence(
                ['content' => '商品のお届けについて'],
                ['content' => '商品の交換について'],
                ['content' => '商品トラブル'],
                ['content' => 'ショップへのお問い合わせ'],
                ['content' => 'その他'],
        )
        ->create();
    }
}
