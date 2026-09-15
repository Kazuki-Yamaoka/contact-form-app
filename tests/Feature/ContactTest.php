<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    /** @test */
    public function お問い合わせフォーム入力ページが表示される(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->get(route('contacts.index'), [$category, $tag]);

        $response->assertStatus(200);
    }

    /** @test */
    public function サンクスページが表示される()
    {
        $category = Category::factory()->make();
        $tag = Tag::factory()->make();

        $response = $this->get(route('contacts.store'), [$category, $tag]);

        $response->assertStatus(200);
    }

    /** @test */
    public function お問い合わせ確認ページが表示される()
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        /*
        $contact = Contact::factory()->make([
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro@example.com',
            'category_id' => $category->id,
        ])->toArray();
        */

        // Factory で住所含むすべてのダミーデータを生成し、関係性のある ID だけ上書き
        $data = Contact::factory()->make([
            'category_id' => $category->id,
            'tag_ids' => [$tag->id], // タグの ID 配列を追加
        ])->toArray();

        $response = $this->post(route('contacts.confirm'), $data);

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('category');
        $response->assertViewHas('tags');
    }

    /** @test */
    public function お問い合わせ入力フォームでメールアドレスの形式で入力されていないとバリデーションエラーになる()
    {
        $contact = Contact::factory()->make([
            'email' => 'invalid-email',
        ])->toArray();

        $response = $this->post(route('contacts.confirm'), $contact);

        $response->assertSessionHasErrors([
            'email',
        ]);
    }

    /** @test */
    public function お問い合わせを入力し確認画面を通過するとタグが中間テーブルに記録される()
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $contact = Contact::factory()->create([
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro@example.com',
            'category_id' => $category->id,
        ]);

        $contact->tags()->attach($tag->id);

        $response = $this->get(route('contacts.thanks'));

        $response->assertStatus(200);
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    /** @test */
    public function お問い合わせの名前が文字列でないとバリデーションエラーになる()
    {
        $category = Category::factory()->create();

        $data = Contact::factory()->make([
            'category_id' => $category->id,
            'first_name' => 123,
            'last_name' => 987,
        ])->toArray();

        $response = $this->post(route('contacts.store'), $data);

        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
        ]);
    }
}
