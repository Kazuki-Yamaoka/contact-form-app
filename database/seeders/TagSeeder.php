<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Tag::factory()
            ->count(5)
            ->sequence(
                ['name' => '質問'],
                ['name' => '要望'],
                ['name' => '不具合報告'],
                ['name' => 'ご意見'],
                ['name' => 'その他'],
            )
            ->create();
    }
}
