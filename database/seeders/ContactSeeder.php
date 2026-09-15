<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ★ データベースからタグを取得しておく
        $tags = Tag::all();

        // タグが存在する場合にランダムに紐付ける
        Contact::factory()->count(20)->create()->each(function ($contact) use ($tags) {
            if ($tags->isNotEmpty()) {
                $contact->tags()->attach(
                    $tags->random(min(3, $tags->count()))->pluck('id')
                );
            }
        });
    }
}
