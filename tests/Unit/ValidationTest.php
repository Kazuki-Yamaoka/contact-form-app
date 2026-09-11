<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Contact;
// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ValidationTest extends TestCase
{
    /**
     * A basic unit test example.
     */

    use RefreshDatabase;

    
    public function ユーザーはお問い合わせをCSV形式でダウンロードできる(): void
    {
        // Arrange
        $user = User::factoty()->create();
        $contact = Contact::factory()->create();
        $category = Category::factory()->create();

        // Act
        $response = $this->actingAs($user)->get(route('contacts.export'), [
            'keyword'     => 'テスト',
            'gender'      => 1,
            'category_id' => 1,
            'date'        => '2026-09-11',
        ]);

        // Assert
        // ① ステータスコード（200 OK）の検証
        $response->assertOk(); // $response->assertStatus(200) と同じ

        // ② ファイルダウンロード用ヘッダーが付与されているか検証（ファイル名の確認）
        $response->assertHeader('content-disposition', 'attachment; filename="contacts.csv"');

        // ③ Content-Type が CSV（またはテキスト）になっているか検証
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    
    public function エクスポートの検索で不正な性別はバリデーションエラーになる(): void
    {
        // Arrange
        $user = User::factoty()->create();
        $contact = Contact::factory()->create();
        $category = Category::factory()->create();

        // Act
        $response = $this->actingAs($user)->get(route('contacts.export'), [
            'keyword'     => 'テスト',
            'gender'      => 99,       // ❌ 不正な性別（0,1,2,3 以外の数値）
            'category_id' => 1,        // ⭕️ 存在するカテゴリーID（例）
            'date'        => '2026-09-11',
        ]);

        // Assert
        $response->assertSessionHasErrors('gender');
    }

    
    public function エクスポートの検索で存在しないカテゴリIDはエラーになる()
    {
        // Arrange
        $user = User::factoty()->create();
        $contact = Contact::factory()->create();
        $category = Category::factory()->create();

        // Act
        $response = $this->actingAs($user)->get(route('contacts.export'), [
            'keyword'     => 'テスト',
            'gender'      => 1,
            'category_id' => 99999, // ❌
            'date'        => '2026-09-11',
        ]);

        // Assert
        $response->assertSessionHasErrors('category_id');
    }

    
    public function お問い合わせ一覧検索ができる()
    {
        $user = User::factoty()->create();
        $contact = Contact::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.index'), [
            'keyword'     => 'テスト',
            'gender'      => 1,
            'category_id' => 1,
            'date'        => '2026-09-11',
        ]);

        // ① ステータスコード（200 OK）の検証
        $response->assertOk();

        // ② 検索結果の画面（ビュー）が返されているか検証
        $response->assertViewIs('admin.contacts.index'); // 該当のビュー名
    }
    
    public function お問い合わせ検索で不正な性別値はバリデーションエラーになる()
    {
        $contact = Contact::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.index'), [
            'keyword'     => 'テスト',
            'gender'      => 99,
            'category_id' => 1,
            'date'        => '2026-09-11',
        ]);

        $response->assertSessionHasErrors('gender');
    }

    
    public function お問い合わせの新規作成ができる()
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create();
        
        $response = $this->post(route('contacts.index'), [
            'category_id' => $category->id,
            'first_name'  => '山田',
            'last_name'   => '太郎',
            'gender'      => 1,
            'email'       => 'test@example.com',
            'tel'         => '09012345678', // 11桁の数値パターンに適合
            'address'     => '東京都渋谷区1-1-1',
            'building'    => 'テストビル101',
            'detail'      => 'お問い合わせの本文です。',
            'tag_ids'     => $tag->pluck('id')->toArray(),
        ]);

        $response->assertRedirect(route('contacts.confirm'));
        $this->assertDatabaseHas('contacts', [
            'detail'      => 'お問い合わせの本文です。',
            'category_id' => $category->id,
            'tag_id' => $tag->id,
        ]);
    }

    
    public function お問い合わせの新規作成で不正な電話番号形式はバリデーションエラーになる()
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create();
        
        $response = $this->post(route('contacts.index'), [
            'category_id' => $category->id,
            'first_name'  => '山田',
            'last_name'   => '太郎',
            'gender'      => 1,
            'email'       => 'test@example.com',
            'tel'         => '090-1234-5678', // ❌ ハイフンが含まれておりregexに違反
            'address'     => '東京都渋谷区1-1-1',
            'building'    => 'テストビル101',
            'detail'      => 'お問い合わせの本文です。',
            'tag_ids'     => $tag->pluck('id')->toArray(),
        ]);

        $response->assertSessionHasErrors('tel');
    }

    
    public function タグの新規登録ができる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.tags.store'), [
            'name' => 'テストカテゴリー',
        ]);

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseHas('tags', [
            'name' => 'テストカテゴリー',
        ]);
    }

    /** @test */
    public function タグ名が空だとバリデーションエラーになる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.tags.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function タグ名は50文字まで入力できる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.tags.store'), [
            'name' => str_repeat('あ', 50),
        ]);

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseHas('tags', [
            'name' => str_repeat('あ', 50),
        ]);
    }

    /** @test */
    public function タグ名の文字数が51文字以上だとバリデーションエラーになる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.tags.store'), [
            'name' => str_repeat('あ', 51),
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function 同じ名前のタグ名を作成するとバリデーションエラーになる()
    {
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => 'テストタグ'
        ]);

        $response = $this->actingAs($user)->post(route('admin.tags.store',), [
            'name' => 'テストタグ',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function タグが更新できる()
    {
        $user = User::factory()->create();

        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.tags.update', $tag), [
            'name' => '更新後のカテゴリー名',
        ]);

        $response->assertRedirect(route('admin.index'), $tag);
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '更新後のカテゴリー名',
        ]);
    }

    /** @test */
    public function すでにあるタグ名に更新しようとするとバリデーションエラーになる()
    {
        $user = User::factory()->create();

        $tag1 = Tag::factory()->create(['name' => '重要']);

        $tag2 = Tag::factory()->create(['name' => '未対応']);


        $response = $this->actingAs($user)->put(route('admin.tags.update', ['tag' => $tag1->id]), [
            'name' => '未対応',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
