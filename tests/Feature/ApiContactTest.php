<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiContactTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    /** @test */
    public function can_index_contact_successfully(): void
    {
        // Arrange
        $category = Category::factory()->create();
        Contact::factory()->count(3)->create([
            'category_id' => $category->id,
        ]);

        // Act
        $response = $this->getJson('/api/v1/contacts');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function お問い合わせ一覧のJSONレスポンス構造が正しい(): void
    {
        // Arrange
        $category = Category::factory()->create(['content' => 'テストカテゴリー']);
        $tag = Tag::factory()->create(['name' => 'テストタグ']);
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'detail' => 'テストお問い合わせ',
            'gender' => 2,
        ]);

        $contact->tags()->attach($tag); //👈 タグを紐付ける！

        // Act
        $response = $this->getJson('/api/v1/contacts');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'category' => [
                        'id',
                        'content',
                    ],
                    'first_name',
                    'last_name',
                    'gender',
                    'gender_label',
                    'email',
                    'tel',
                    'address',
                    'building',
                    'detail',
                    'tags' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function お問い合わせ一覧のJSONレスポンス内容が正しい(): void
    {
        // Arrange
        $category = Category::factory()->create(['content' => 'テストカテゴリー']);
        $contactMan = Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 1,
            'detail' => '男性のお問い合わせ',
        ]);
        $contactWoman = Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 2,
            'detail' => '女性のお問い合わせ',
        ]);

        // Act
        $response = $this->getJson('/api/v1/contacts');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $contactMan->id,
            'gender' => 1,
            'gender_label' => '男性',
            'detail' => '男性のお問い合わせ',
        ]);
        $response->assertJsonFragment([
            'id' => $contactWoman->id,
            'gender' => 2,
            'gender_label' => '女性',
            'detail' => '女性のお問い合わせ',
        ]);
        $response->assertJsonFragment([
            'content' => 'テストカテゴリー',
        ]);
    }


    /** @test */
    public function test_can_search_contacts_by_keyword_and_gender(): void
    {
        // Arrange: 検索対象と対象外のデータを作成
        $category = Category::factory()->create();
        
        // ヒットさせたいデータ
        Contact::factory()->create([
            'first_name'  => '山田',
            'gender'      => 1,
            'category_id' => $category->id,
        ]);
        
        // ヒットさせないデータ
        Contact::factory()->create([
            'first_name'  => '佐藤',
            'gender'      => 2,
        ]);

        // Act: 検索パラメータを付与してリクエスト
        $response = $this->getJson('/api/v1/contacts?' . http_build_query([
            'keyword' => '山田',
            'gender'  => 1,
        ]));

        // Assert: 200 OK かつ、絞り込まれて 1 件のみ返ってくることを確認
        $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.first_name', '山田');
    }

    /** @test */
    public function test_can_paginate_contacts(): void
    {
        // Arrange: 15件のデータを作成
        Contact::factory()->count(15)->create();

        // Act: per_page=5, page=2 を指定
        $response = $this->getJson('/api/v1/contacts?' . http_build_query([
            'per_page' => 5,
            'page'     => 2,
        ]));

        // Assert: 指定した5件が取得でき、ページネーション情報（meta）が正しいか
        $response->assertStatus(200)
                ->assertJsonCount(5, 'data')
                ->assertJsonPath('meta.current_page', 2)
                ->assertJsonPath('meta.per_page', 5)
                ->assertJsonPath('meta.total', 15);
    }

    /** @test */
    public function test_returns_422_when_search_parameters_are_invalid(): void
    {
        // Act: 不正な値（存在しない性別、不正な日付フォーマット、文字列のページ数など）を指定
        $response = $this->getJson('/api/v1/contacts?' . http_build_query([
            'gender'   => 99,                   // in:1,2,3 違反
            'date'     => 'invalid-date-format', // date 違反
            'per_page' => 'not-a-number',       // integer 違反
        ]));

        // Assert: 422 エラーと該当のフィールドにバリデーションエラーが発生しているか確認
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['gender', 'date', 'per_page']);
    }

     /** @test */
    public function 特定のお問い合わせをJSON形式で取得できる(): void
    {
        // Arrange
        $tag = Tag::factory()->create(['name' => 'テストタグ']);
        $category = Category::factory()->create(['content' => 'テストカテゴリー']);
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 3,
            'email' => 'test@example',
            'detail' => 'テストお問い合わせ',
        ]);

        $contact->tags()->attach($tag);

        // Act
        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                    'id',
                    'category' => [
                        'id',
                        'content',
                    ],
                    'first_name',
                    'last_name',
                    'gender',
                    'gender_label',
                    'email',
                    'tel',
                    'address',
                    'building',
                    'detail',
                    'tags' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],

            ],
        ]);
    }

    /** @test */
    public function 特定のタスクのJSONレスポンス内容が正しい(): void
    {
        // Arrange
        $category = Category::factory()->create(['content' => 'テストカテゴリー']);
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 3,
            'email' => 'test@example.com',
            'detail' => 'テストお問い合わせ',
        ]);

        // Act
        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $contact->id,
                'category' => [
                    'id' => $category->id,
                    'content' => 'テストカテゴリー',
                    ],
                'gender' => 3,
                'gender_label' => 'その他',
                'email' => 'test@example.com',
                'detail' => 'テストお問い合わせ',
            ],
        ]);
    }

    /** @test */
    public function 存在しないお問い合わせIDで詳細表示しようとすると404エラーを返す(): void
    {
        // Act
        $response = $this->getJson('/api/v1/contacts/99999');

        // Assert
        $response->assertNotFound(); // 404
    }

    /** @test */
    public function 無効なお問い合わせIDで404エラーを返す(): void
    {
        // Act
        $response = $this->getJson('/api/v1/contacts/invalid');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function お問い合わせをJSON形式で新規作成できる(): void
    {
        // Arrange
        $category = Category::factory()->create();

        // Act
        $response = $this->postJson('/api/v1/contacts', [
            'category_id' => $category->id,
            'first_name'  => '山田',
            'last_name'   => '太郎',
            'gender'      => 1,
            'email'       => 'test@example.com',
            'tel'         => '09012345678',
            'address'     => '東京都渋谷区...',
            'building'    => 'テストビル101',
            'detail'      => 'お問い合わせ内容のテストです。',
        ]);

        // Assert
        $response->assertStatus(201);
        // $response->assertJsonCount(1, 'data');
        $response->assertJson([
            'data' => [
                'first_name' => '山田',
                'email'      => 'test@example.com',
            ],
        ]);

        // データベースに保存されたか検証
        $this->assertDatabaseHas('contacts', [
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function test_returns_422_when_create_parameters_are_invalid(): void
    {
        // Act: 不正な値（存在しない性別、不正な日付フォーマット、文字列のページ数など）を指定
        $response = $this->postJson('/api/v1/contacts?' . http_build_query([
            'gender'   => 99,                   // in:1,2,3 違反
            'email' => 'invalid-email',
            'tel' => 'invalid-string',
        ]));

        // Assert: 422 エラーと該当のフィールドにバリデーションエラーが発生しているか確認
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['gender', 'email', 'tel']);
    }

    /** @test */
    public function お問い合わせをJSON形式で更新できる(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name'  => '山田',
            'last_name'   => '旧太郎',
            'gender'      => 1,
            'email'       => 'old@example.com',
            'tel'         => '09012345678',
            'address'     => '東京都渋谷区...',
            'building'    => 'テストビル101',
            'detail'      => 'お問い合わせ内容のテストです。',
        ]);

        // Act
        $response = $this->putJson("/api/v1/contacts/{$contact->id}", [
            'category_id' => $category->id,
            'first_name'  => '山田',
            'last_name'   => '新太郎', // 👈 変更する値
            'gender'      => 1,
            'email'       => 'new@example.com', // 👈 変更する値
            'tel'         => '09012345678',
            'address'     => '東京都渋谷区...',
            'building'    => 'テストビル101',
            'detail'      => 'お問い合わせ内容のテストです。',
        ]);

        // Assert
        $response->assertStatus(200);

        // データベースに保存されたか検証
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'last_name'  => '新太郎',
            'email' => 'new@example.com',
        ]);
    }

    /** @test */
    public function 存在しないお問い合わせIDで更新しようとすると404エラーを返す(): void
    {
        // Act
        $response = $this->putJson('/api/v1/contacts/99999');

        // Assert
        $response->assertNotFound(); // 404
    }

    /** @test */
    public function test_returns_422_when_update_parameters_are_invalid(): void
    {
        // Arrange: 更新対象のコンタクトを 1 件作成
        $contact = Contact::factory()->create();

        // Act: 不正な値（存在しない性別、不正な日付フォーマット、文字列のページ数など）を指定
        $response = $this->putJson("/api/v1/contacts/{$contact->id}", [
            'gender'   => 99,                   // in:1,2,3 違反
            'email' => 'invalid-email',
            'tel' => 'invalid-string',
        ]);

        // Assert: 422 エラーと該当のフィールドにバリデーションエラーが発生しているか確認
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['gender', 'email', 'tel']);
    }

    /** @test */
    public function お問い合わせをJSON形式で削除できる(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name'  => '山田',
            'last_name'   => '太郎',
            'gender'      => 1,
            'email'       => 'taro@example.com',
            'tel'         => '09012345678',
            'address'     => '東京都渋谷区...',
            'building'    => 'テストビル101',
            'detail'      => 'お問い合わせ内容のテストです。',
        ]);

        // Act
        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        // Assert
        $response->assertStatus(204);

        // データベースに保存されたか検証
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
            'last_name'  => '太郎',
            'email' => 'taro@example.com',
        ]);
    }

    /** @test */
    public function 存在しないお問い合わせIDで削除しようとすると404エラーを返す(): void
    {
        // Act
        $response = $this->deleteJson('/api/v1/contacts/99999');

        // Assert
        $response->assertNotFound(); // 404
    }
}