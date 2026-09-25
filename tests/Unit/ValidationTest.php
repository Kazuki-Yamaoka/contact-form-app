<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
// use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    use RefreshDatabase;

    /** @test */
    public function ユーザーはお問い合わせを_cs_v形式でダウンロードできる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        // Act
        $response = $this->actingAs($user)->get(route('contacts.export'), [
            'keyword' => 'テスト',
            'gender' => 1,
            'category_id' => 1,
            'date' => '2026-09-11',
        ]);

        // Assert
        // ① ステータスコード（200 OK）の検証
        $response->assertOk(); // $response->assertStatus(200) と同じ

        // ② ファイルダウンロード用ヘッダーが付与されているか検証（ファイル名の確認）
        // $response->assertHeader('content-disposition', 'attachment; filename="contacts.csv"');
        // ⭕ 修正後（contacts_ から始まり .csv で終わるかを正規表現でチェック）
        $this->assertMatchesRegularExpression(
            '/attachment; filename="contacts_\d{8}_\d{6}\.csv"/',
            $response->headers->get('content-disposition')
        );

        // ③ Content-Type が CSV（またはテキスト）になっているか検証
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function エクスポートの検索で不正な性別はバリデーションエラーになる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        // Act
        $response = $this->actingAs($user)->get(route('contacts.export', [
            'keyword' => 'テスト',
            'gender' => 99,       // ❌ 不正な性別（0,1,2,3 以外の数値）
            'category_id' => 1,        // ⭕️ 存在するカテゴリーID（例）
            'date' => '2026-09-11',
        ]));

        // Assert
        $response->assertSessionHasErrors('gender');
    }

    /** @test */
    public function エクスポートの検索で存在しないカテゴリ_i_dはエラーになる()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        // Act
        $response = $this->actingAs($user)->get(route('contacts.export', [
            'keyword' => 'テスト',
            'gender' => 1,
            'category_id' => 99999, // ❌
            'date' => '2026-09-11',
        ]));

        // Assert
        $response->assertSessionHasErrors('category_id');
    }

    /** @test */
    public function お問い合わせ一覧検索ができる()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->get(route('admin.index'), [
            'keyword' => 'テスト',
            'gender' => 1,
            'category_id' => 1,
            'date' => '2026-09-11',
        ]);

        // ① ステータスコード（200 OK）の検証
        $response->assertOk();

        // ② 検索結果の画面（ビュー）が返されているか検証
        $response->assertViewIs('admin.index');
    }

    /** @test */
    public function お問い合わせ検索で不正な性別値はバリデーションエラーになる()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->get(route('admin.index', [
            'keyword' => 'テスト',
            'gender' => 99,
            'category_id' => 1,
            'date' => '2026-09-11',
        ]));

        $response->assertSessionHasErrors('gender');
    }

    /** @test */
    public function お問い合わせの新規作成ができる()
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $response = $this->post(route('contacts.store'), [
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678', // 11桁の数値パターンに適合
            'address' => '東京都渋谷区1-1-1',
            'building' => 'テストビル101',
            'detail' => 'お問い合わせの本文です。',
            'tag_ids' => $tag->pluck('id')->toArray(),
        ]);

        $response->assertRedirect(route('contacts.thanks'));
        $this->assertDatabaseHas('contacts', [
            'detail' => 'お問い合わせの本文です。',
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('contact_tag', [ // ※中間テーブル名が contact_tags の場合はそちらを指定
            'tag_id' => $tag->id, // または $tag->first()->id
        ]);
    }

    /** @test */
    public function お問い合わせの新規作成で不正な電話番号形式はバリデーションエラーになる()
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $response = $this->post(route('contacts.store'), [
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '090-1234-5678', // ❌ ハイフンが含まれておりregexに違反
            'address' => '東京都渋谷区1-1-1',
            'building' => 'テストビル101',
            'detail' => 'お問い合わせの本文です。',
            'tag_ids' => $tag->pluck('id')->toArray(),
        ]);

        $response->assertSessionHasErrors('tel');
    }

    /** @test */
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
            'name' => 'テストタグ',
        ]);

        $response = $this->actingAs($user)->post(route('admin.tags.store'), [
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

    /** @test */
    public function 正しいフィルタ条件で_cs_vエクスポートができる()
    {
        $user = User::factory()->create();
        // 1. 前提データの準備（カテゴリを作成）
        $category = Category::factory()->create(['content' => '商品について']);

        // 抽出対象のデータ（男性）
        $targetContact = Contact::factory()->create([
            'last_name' => '佐藤',
            'first_name' => '太郎',
            'gender' => 1, // 男性
            'email' => 'sato@example.com',
            'category_id' => $category->id,
        ]);

        // 抽出対象外のデータ（女性）
        $otherContact = Contact::factory()->create([
            'last_name' => '鈴木',
            'first_name' => '花子',
            'gender' => 2, // 女性
            'email' => 'suzuki@example.com',
            'category_id' => $category->id,
        ]);

        // 2. 検索条件（gender=1）を付与してエクスポートを呼び出す
        $response = $this->actingAs($user)->get(route('contacts.export', ['gender' => 1]));

        // 3. レスポンス（ステータス・ヘッダー）の検証
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // 4. CSVの中身（コンテンツ）の検証
        $content = $response->streamedContent();

        // ヘッダー行が存在するか
        $this->assertStringContainsString('ID,氏名,性別,メール', $content);

        // フィルタで指定した男性のデータが含まれているか
        $this->assertStringContainsString('佐藤 太郎', $content);
        $this->assertStringContainsString('男性', $content);
        $this->assertStringContainsString('sato@example.com', $content);

        // フィルタ対象外である女性のデータが含まれていない（除外されている）か
        $this->assertStringNotContainsString('鈴木 花子', $content);
        $this->assertStringNotContainsString('suzuki@example.com', $content);
    }

    /** @test */
    public function cs_vエクスポートで不正な性別が指定された場合はエラーで拒否される()
    {
        $user = User::factory()->create();
        // 1. 存在しない性別（例: 99）を指定して実行
        $response = $this->actingAs($user)->get(route('contacts.export', ['gender' => 99]));

        // 2. HTTPステータスコードが 302 であることを検証
        $response->assertStatus(302);

        // 3. 'gender' のキーでバリデーションエラーが発生していることを検証
        $response->assertSessionHasErrors(['gender']);
    }

    /** @test */
    public function cs_vエクスポートで存在しないカテゴリー_i_dが指定された場合はエラーで拒否される()
    {
        $user = User::factory()->create();
        // 1. データベースに存在しないカテゴリID（例: 9999）を指定して実行
        $response = $this->actingAs($user)->get(route('contacts.export', ['category_id' => 9999]));

        // 2. HTTPステータスコードが 302 であることを検証
        $response->assertStatus(302);

        // 3. 'category_id' のキーでバリデーションエラーが発生していることを検証
        $response->assertSessionHasErrors(['category_id']);
    }
}
