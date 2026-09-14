<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
        $category = Category::create();
        $tag = Tag::create();

        $response = $this->get(route('contacts.index'), [$category, $tag]);

        $response->assertStatus(200);
        $response->assertViewHas('category', 'tag');
    }

    /** @test */
    public function サンクスページが表示される()
    {
        $category = Category::create();
        $tag = Tag::create();

        $response = $this->post(route('contacts.store'), [$category, $tag]);

        $response->assertStatus(200);
        $response->assertRedirect(route('contacts.thanks'));
    }

    /** @test */
    public function お問い合わせ確認ページが表示される()
    {
        $category = Category::create();

        $response = $this->post(route('contacts.confirm'), [
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro@example.com',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(200);
        $response->assertViewHas('category', 'tag');
        $this->assertDatabaseHas('contacts', [
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro@example.com',
            'category_id' => $category->id,
        ]);
    }

    /** @test */
    public function お問い合わせ入力フォームでメールアドレスの形式で入力されていないとバリデーションエラーになる()
    {
        $response = $this->post(route('contacts.confirm'), [
            'email' => 'invalid-email',
        ]);

        $this->assertSessionHasErrors('contacts', [
            'email',
        ]);
    }

    /** @test */
    public function お問い合わせを入力し確認画面を通過するとタグが中間テーブルに記録される()
    {
        $category = Category::create();
        $tag = Tag::create();
        $contact = Contact::create();

        $contact->tags()->attach($tag->id);

        $response = $this->post(route('contacts.store'), [
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro@example.com',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => '$tag->id',
        ]);
        $response->assertRedirect(route('contacts.thanks'));
    }

        /** @test */
    public function お問い合わせの名前が文字列でないとバリデーションエラーになる()
    {
        $response = $this->post(route('contacts.store'), [
            'first_name' => '11',
            'last_name' => '99',
        ]);

        $this->assertSessionHasErrors('contacts', [
            'first_name',
            'last_name',
        ]);
    }
}