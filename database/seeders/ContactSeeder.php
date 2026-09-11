<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Contact::factory()->count(20)->create()->each(function ($contact) use ($tags) {
            $contact->tags()->attach(
            $tags->random(3)->pluck('id')
            );
        });
    }
    
}
