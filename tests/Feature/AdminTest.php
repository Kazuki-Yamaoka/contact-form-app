<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    /** @test */
    public function 認証されたユーザーのみが管理ダッシュボードにアクセスできる()
    {
        $user = User::create();

        $response = $this->actingAs($user)->get(route('admin.index'));

        $response->assertRedirect(route('admin.index'));
    }

    /** @test */
    public function 未認証ユーザーは管理ダッシュボードにアクセスしようとするとログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('admin.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function ページネーションが7件ごとにされている()
    {
        $user = User::create();
        $contact = Contact::count(30)->create();
        $category = Category::create();

        $response = $this->actingAs($user)->get(route('admin.index'), [
            'per_page' => 7,
        ]);

        $response->assertOk();

        $response->assertViewIs('admin.contacts.index');

    }

    /** @test */
    public function キーワード検索時に該当データが7件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();

        // 検索にヒットさせたいデータ（8件作成）
        Contact::factory()->count(8)->create([
            'fullname' => 'テスト太郎',
        ]);

        // 検索にヒットさせないデータ（3件作成）
        Contact::factory()->count(3)->create([
            'fullname' => 'ダミー花子',
        ]);

        // 「テスト」で検索実行（1ページ目）
        $response = $this->actingAs($user)->get(route('admin.index', [
            'keyword' => 'テスト',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('contacts', function ($contacts) {
            // 全体の該当件数は8件、1ページ目には7件表示されているか
            return $contacts->total() === 8 && $contacts->count() === 7;
        });

        // 2ページ目の取得
        $response2 = $this->actingAs($user)->get(route('admin.index', [
            'keyword' => 'テスト',
            'page' => 2,
        ]));

        $response2->assertViewHas('contacts', function ($contacts) {
            // 2ページ目には残りの1件が表示されているか
            return $contacts->count() === 1;
        });
    }

    /** @test */
    public function 性別フィルタ（男性）指定時に7件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();

        // 男性データを8件作成（例: gender = 1）
        Contact::factory()->count(8)->create(['gender' => 1]);
        // 女性データを5件作成（例: gender = 2）
        Contact::factory()->count(5)->create(['gender' => 2]);

        // 性別「男性(1)」で絞り込み実行
        $response = $this->actingAs($user)->get(route('admin.index', [
            'gender' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->total() === 8 && $contacts->count() === 7;
        });
    }

    
    /** @test */
    public function カテゴリ検索で7件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();

        $category = Category::count(2)->factory()->create();

        // カテゴリーid(1)を8件作成
        Contact::factory()->count(8)->create(['category_id' => 1]);
        // カテゴリーid(2)を5件作成
        Contact::factory()->count(5)->create(['category_id' => 2]);

        // 性別「カテゴリーid(1)」で絞り込み実行
        $response = $this->actingAs($user)->get(route('admin.index', [
            'category_id' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->total() === 8 && $contacts->count() === 7;
        });
    }

        /** @test */
    public function 日付検索で7件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();

        // カテゴリーid1を8件作成
        Contact::factory()->count(8)->create(['date' => '2026-9-13']);
        // カテゴリーid1を5件作成
        Contact::factory()->count(5)->create(['date' => '2026-9-14']);

        // 性別「男性(1)」で絞り込み実行
        $response = $this->actingAs($user)->get(route('admin.index', [
            'date' => '2026-9-13',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->total() === 8 && $contacts->count() === 7;
        });
    }

    /** @test */
    public function カテゴリー情報付きでお問い合わせの詳細が取得できる()
    {
        $user = User::create();
        $category = Category::create();
        $contact = Contact::create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->get(route('admin.show'), $contact);

        $response->assertStatus(200);
        $response->assertViewHas('contacts', [
            'category_id' => $category->id,
        ]);
    }

    /** @test */
    public function お問い合わせの削除をして管理ダッシュボードにリダイレクトされる()
    {
        $user = User::create();
        $contact = Contact::create();

        $response = $this->actingAs($user)->delete(route('admin.delete'), $contact);

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /** @test */
    public function 認証済みユーザーがタグ編集画面を表示できる()
    {
        $user = User::create();
        $tag = Tag::create();

        $response = $this->actingAs($user)->get(route('admin.tags.edit'));

        $response->assertStatus(200);
        $response->assertRedirect('admin.index');
    }

    
    /** @test */
    public function 認証済みユーザーがタグを作成できる()
    {
        $user = User::create();

        $response = $this->actingAs($user)->post(route('admin.tags.store'));

        $response->assertStatus(201);
        $response->assertRedirect('admin.index');
    }

    /** @test */
    public function 認証済みユーザーがタグを更新できる()
    {
        $user = User::create();
        $tag = Tag::create();

        $response = $this->actingAs($user)->put(route('admin.tags.update'), $tag);

        $response->assertStatus(200);
        $response->assertRedirect('admin.index');
    }

    
    /** @test */
    public function 認証済みユーザーがタグを削除できる()
    {
        $user = User::create();
        $tag = Tag::create();

        $response = $this->actingAs($user)->delete(route('admin.tags.delete'), $tag);

        $response->assertStatus(204);
        $response->assertRedirect('admin.index');
    }

    /** @test */
    public function 未認証ユーザーはタグ編集画面にアクセスしようとするとログイン画面にリダイレクトされる()
    {
        $tag = Tag::create();

        $response = $this->get(route('admin.tags.edit'), $tag);

        $response->assertRedirect('login');
    }

    /** @test */
    public function ログイン済みユーザーはフィルタ条件付きでCSVをDLできる()
    {
        $user = User::create();
        Contact::count(7)->create(['gender' => 1]);
        Contact::count(8)->create(['gender' => 2]);

        $response = $this->actingAs($user)->get(route('admin.export'), [
            'gender' => 1,
        ]);

        $response->assertOk();

        // ② ファイルダウンロード用ヘッダーが付与されているか検証（ファイル名の確認）
        $response->assertHeader('content-disposition', 'attachment; filename="contacts.csv"');

        // ③ Content-Type が CSV（またはテキスト）になっているか検証
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    }

    /** @test */
    public function ログイン済みユーザーはフィルタ無指定時は新着順でCSVをDLできる()
    {
        $user = User::create();
        $contact1 = Contact::create(['created_at' => now()->subDays(2)]);
        $contact2 = Contact::create(['created_at' => now()->subDays()]);
        $contact3 = Contact::create(['created_at' => now()]);

        $response = $this->actingAs($user)->get(route('contacts.export'));

        $response->assertStatus(200);

// レスポンスのCSV文字列を取得
        $csvContent = $response->streamedContent(); // または $response->getContent()

        // CSVの内容を行単位（配列）に分割する
        $lines = explode("\n", trim($csvContent));

        // 1行目はヘッダー行（見出し）のはずなので、データ行は2行目（インデックス1）から
        // 新着順（最新 -> 中間 -> 古い）になっているかを並び順通りに検証
        $this->assertStringContainsString($contact3->fullname, $lines[1]); // 1番目（最新）
        $this->assertStringContainsString($contact2->fullname, $lines[2]); // 2番目（中間）
        $this->assertStringContainsString($contact1->fullname, $lines[3]); // 3番目（古い）
    }
}
