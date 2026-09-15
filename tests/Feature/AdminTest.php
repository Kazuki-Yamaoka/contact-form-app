<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.index'));

        $response->assertStatus(200);
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
        $user = User::factory()->create();
        $contact = Contact::factory()->count(30)->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.index'), [
            'per_page' => 7,
        ]);

        $response->assertOk();

        $response->assertViewIs('admin.index');

    }

    /** @test */
    public function キーワード検索時に該当データが7件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();

        // 検索にヒットさせたいデータ（8件作成）
        Contact::factory()->count(8)->create([
            'first_name' => 'テスト',
            'last_name' => '太郎',
            'email' => 'test@example.com',
        ]);

        // 検索にヒットさせないデータ（3件作成）
        Contact::factory()->count(3)->create([
            'first_name' => 'ダミー',
            'last_name' => '二郎',
            'email' => 'dummy@example.com',
        ]);

        // 「テスト」で検索実行（1ページ目）
        $response = $this->actingAs($user)->get(route('admin.index', [
            'keyword' => 'テスト',
        ]), );

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
        $response = $this->actingAs($user)->get(route('admin.index', ['gender' => 1]));

        $response->assertStatus(200);
        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->total() === 8 && $contacts->count() === 7;
        });
    }

    /** @test */
    public function カテゴリ検索で7件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->count(2)->create();

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
        Contact::factory()->count(8)->create(['created_at' => '2026-9-13']);
        // カテゴリーid1を5件作成
        Contact::factory()->count(5)->create(['created_at' => '2026-9-14']);

        // 性別「男性(1)」で絞り込み実行
        $response = $this->actingAs($user)->get(route('admin.index', [
            'created_at' => '2026-9-13',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->total() === 13 && $contacts->count() === 7;
        });
    }

    /** @test */
    public function カテゴリー情報付きでお問い合わせの詳細が取得できる()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->get(route('admin.show', $contact->id));

        $response->assertStatus(200);
        $response->assertViewHas('contact', function ($contact) use ($category) {
            return $contact->category_id === $category->id
                && $contact->relationLoaded('category');
        });
    }

    /** @test */
    public function お問い合わせの削除をして管理ダッシュボードにリダイレクトされる()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->delete(route('admin.destroy', $contact->id));

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /** @test */
    public function 認証済みユーザーがタグ編集画面を表示できる()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.tags.edit', $tag->id));

        $response->assertStatus(200);
    }

    /** @test */
    public function 認証済みユーザーがタグを作成できる()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->make()->toArray();

        $response = $this->actingAs($user)->post(route('admin.tags.store'), $tag);

        // $response->assertSessionHasNoErrors();

        $response->assertRedirect(route('admin.index'));
    }

    /** @test */
    public function 認証済みユーザーがタグを更新できる()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['name' => 'テスト']);

        $response = $this->actingAs($user)->put(route('admin.tags.update', $tag->id), [
            'name' => '新テスト',
        ]);

        $response->assertRedirect(route('admin.index'));
        // データベースが更新されたか確認
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '新テスト',
        ]);
    }

    /** @test */
    public function 認証済みユーザーがタグを削除できる()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->delete(route('admin.tags.destroy', $tag->id));

        $response->assertRedirect(route('admin.index'));
    }

    /** @test */
    public function 未認証ユーザーはタグ編集画面にアクセスしようとするとログイン画面にリダイレクトされる()
    {
        $tag = Tag::factory()->create();

        $response = $this->get(route('admin.tags.edit', $tag->id));

        $response->assertRedirect('login');
    }

    /** @test */
    public function ログイン済みユーザーはフィルタ条件付きで_cs_vを_d_lできる()
    {
        $user = User::factory()->create();
        Contact::factory()->count(7)->create(['gender' => 1]);
        Contact::factory()->count(8)->create(['gender' => 2]);

        $response = $this->actingAs($user)->get(route('contacts.export', [
            'gender' => 1,
        ]));

        $response->assertOk();

        // ② ファイルダウンロード用ヘッダー（ファイル名の部分一致）を検証
        $contentDisposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment; filename="contacts_', $contentDisposition);

        // ③ Content-Type が CSV であることを検証
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    }

    /** @test */
    public function ログイン済みユーザーはフィルタ無指定時は新着順で_cs_vを_d_lできる()
    {
        $user = User::factory()->create();
        $contact1 = Contact::factory()->create(['created_at' => now()->subDays(2)]);
        $contact2 = Contact::factory()->create(['created_at' => now()->subDays()]);
        $contact3 = Contact::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($user)->get(route('contacts.export'));

        $response->assertStatus(200);

        // レスポンスのCSV文字列を取得
        $csvContent = $response->streamedContent(); // または $response->getContent()

        // CSVの内容を行単位（配列）に分割する
        $lines = explode("\n", trim($csvContent));

        // 1行目はヘッダー行（見出し）のはずなので、データ行は2行目（インデックス1）から
        // 新着順（最新 -> 中間 -> 古い）になっているかを並び順通りに検証
        $this->assertStringContainsString($contact3->created_at, $lines[1]); // 1番目（最新）
        $this->assertStringContainsString($contact2->created_at, $lines[2]); // 2番目（中間）
        $this->assertStringContainsString($contact1->created_at, $lines[3]); // 3番目（古い）
    }
}
