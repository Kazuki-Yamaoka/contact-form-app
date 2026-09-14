<?php

namespace Tests\Unit;


use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase; 

class ApiValidationTest extends TestCase
{
    /**
     * A basic unit test example.
     */

    use RefreshDatabase;

    /** @test */
    public function お問い合わせ一覧を検索してAPIで取得できる(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $contacts = Contact::factory()->count(3)->create([
            'category_id' => $category->id,
        ]);
        foreach ($contacts as $contact) {
            $contact->tags()->attach($tag->id);
        }

        // Act: API用の getJson と正確なルート名を使用
        $response = $this->getJson(route('v1.contacts.index', [
            'keyword'     => 'テスト',
            'gender'      => 1,
            'category_id' => $category->id,
            'date'        => '2026-09-12',
        ]));

        // Assert
        $response->assertStatus(200)
                    ->assertJsonStructure([
                        'data' => [
                            '*' => [
                                'id',
                                'category',
                                'first_name',
                                'last_name',
                                'gender',
                                'email',
                                'tel',
                                'address',
                                'building',
                                'detail',
                                'tags',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                        'links' => [
                            'first',
                            'last',
                            'prev',
                            'next',
                        ],
                        'meta' => [
                            'current_page',
                            'from',
                            'last_page',
                            'links',
                            'path',
                            'per_page',
                            'to',
                            'total',
                        ],
                    ]);
    }

    /** @test */
    public function 性別が不正な値で404エラーを返す()
    {
        // Act: 不正な性別値を指定して API 検索
        $response = $this->getJson(route('v1.contacts.index', [
            'gender' => 99,
        ]));

        // Assert: 422 エラーと JSON のエラーメッセージ構造を検証
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['gender']);
    }

    /** @test */
    public function カテゴリーが不正な値で404エラーを返す()
    {
        // Act: 不正な性別値を指定して API 検索
        $response = $this->getJson(route('v1.contacts.index', [
            'category_id' => 99,
        ]));

        // Assert: 422 エラーと JSON のエラーメッセージ構造を検証
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['category_id']);
    }

    /** @test */
    public function test_returns_422_when_date_is_invalid_string()
    {
        $response = $this->getJson(route('v1.contacts.index', [
            'date' => 'invalid-date-string', // 👈 文字列を渡す
        ]));

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['date']);
    }

    /** @test */
    public function test_returns_200_when_per_page_is_valid_max_values()
    {
        $response = $this->getJson(route('v1.contacts.index', [
            'per_page' => '100', // 👈 文字列を渡す
        ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function test_returns_422_when_per_page_is_invalid_values()
    {
        $response = $this->getJson(route('v1.contacts.index', [
            'per_page' => '101', // 👈 文字列を渡す
        ]));

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * 正常データで登録が成功する（201 Created）
     */

    /** @test */
    public function test_can_store_contact_successfully(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->postJson(route('v1.contacts.store'), [
            'category_id' => $category->id,
            'first_name'  => 'Yamada',
            'last_name'   => 'Taro',
            'gender'      => 1,
            'email'       => 'test@example.com',
            'tel'         => '09012345678',
            'address'     => 'Tokyo',
            'building'    => 'Building 101',
            'detail'      => 'Test detail content.',
            'tag_ids'     => [$tag->id], // 👈 配列として渡す
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('contacts', [
            'email' => 'test@example.com',
        ]);
        $this->assertDatabaseHas('contact_tag', [
        'tag_id' => $tag->id,
        ]);
    }

    /**
     * 必須項目が空の場合に 422 エラーを返す
     */

    /** @test */
    public function test_store_fails_when_required_fields_are_missing(): void
    {
        $response = $this->postJson(route('v1.contacts.store'), []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'category_id',
                     'first_name',
                     'last_name',
                     'gender',
                     'email',
                     'tel',
                     'address',
                     'detail',
                 ]);
    }

    /**
     * メールアドレスの形式が不正・または文字数オーバーの場合に 422 エラー
     */

    /** @test */
    public function test_store_fails_when_email_is_invalid(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson(route('v1.contacts.store'), [
            'category_id' => $category->id,
            'first_name'  => 'Yamada',
            'last_name'   => 'Taro',
            'gender'      => 1,
            'email'       => 'invalid-email-format', // 不正なメール形式
            'tel'         => '09012345678',
            'address'     => 'Tokyo',
            'detail'      => 'Test detail',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * 電話番号が桁数オーバーまたは数値・ハイフン以外の場合に 422 エラー
     */

    /** @test */
    public function test_store_fails_when_tel_is_invalid(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson(route('v1.contacts.store'), [
            'category_id' => $category->id,
            'first_name'  => 'Yamada',
            'last_name'   => 'Taro',
            'gender'      => 1,
            'email'       => 'test@example.com',
            'tel'         => '090-1234-5678-99999999', // 長すぎる電話番号
            'address'     => 'Tokyo',
            'detail'      => 'Test detail',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['tel']);
    }
}