<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Contact;
use PHPUnit\Framework\TestCase;

class RelationTest extends TestCase
{
    /**
     * A basic unit test example.
     */

    
    public function ひとつのカテゴリから複数のお問い合わせが取得できる()
    {
        // 1. Arrange（準備）
    $category = Category::factory()->create();

    // 親の category_id を指定して Contact を 3件作成
    Contact::factory()->count(3)->create([
        'category_id' => $category->id,
    ]);

    // 関係ない別のカテゴリのContactも作っておく（混ざらないかの検証用）
    Contact::factory()->count(2)->create();

    // 2. Act（実行）
    $contacts = $category->contacts;

    // 3. Assert（検証）
    // 他のカテゴリのContactは含まれず、自分の配下の 3件 だけが取得できているか
    expect($contacts)->toHaveCount(3);
    
    // データベース上の判定（PHPUnitスタイルの場合）
    // $this->assertCount(3, $category->contacts);
    }

    
    public function お問い合わせが特定のカテゴリーに属し、複数のタグと正しく同期できる()
    {
        // 1. Arrange（事前準備）
        // --------------------------------------------------
        $user = User::factory()->create();
        
        // カテゴリーを作成
        $category = Category::factory()->create(['name' => '商品に関するお問い合わせ']);

        // 同期テスト用のタグを3つ作成
        $tags = Tag::factory()->count(3)->create();
        
        // お問い合わせを作成（初期状態ではタグなし）
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        // --------------------------------------------------
        // 2. Act（実行：更新処理リクエスト）
        // --------------------------------------------------
        // 上記で作成した $category のID と $tags のID配列を送信
        $response = $this->actingAs($user)->put(route('admin.contacts.update', ['contact' => $contact->id]), [
            'category_id' => $category->id,
            'first_name'  => '山田',
            'last_name'   => '太郎',
            'gender'      => 1,
            'email'       => 'test@example.com',
            'tel'         => '09012345678',
            'address'     => '東京都渋谷区1-1-1',
            'detail'      => 'お問い合わせ内容です。',
            
            // ★ 作成した3つのタグのID配列を渡す（pluck('id')->toArray() で [1, 2, 3] のような配列になる）
            'tag_ids'     => $tags->pluck('id')->toArray(), 
        ]);

        // --------------------------------------------------
        // 3. Assert（検証）
        // --------------------------------------------------
        // ① ステータスの検証（リダイレクト 302 または 200 OK）
        $response->assertStatus(302);

        // ② カテゴリーとのリレーション（belongsTo）の検証
        $contact->refresh(); // データベースの最新状態に更新
        expect($contact->category_id)->toBe($category->id);
        expect($contact->category->name)->toBe('商品に関するお問い合わせ');

        // ③ 複数のタグとの同期（sync / belongsToMany）の検証
        // 中間テーブル（contact_tag）に3つのタグが紐付いているか検証
        expect($contact->tags)->hasCount(3);
        
        // 特定のタグIDがすべて紐付いているかデータベースでアサート
        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id'     => $tag->id,
            ]);
        }
    }

    
    public function ひとつのタグが複数のお問い合わせに中間テーブルを介して紐づいている()
    {
        // 1. Arrange（事前準備）
        // --------------------------------------------------
        $category = Category::factory()->create();

        // 共通で紐付ける「1つのタグ」を作成
        $tag = Tag::factory()->create(['name' => '重要']);

        // 複数（例: 3件）のお問い合わせを作成
        $contacts = Contact::factory()->count(3)->create([
            'category_id' => $category->id,
        ]);

        // --------------------------------------------------
        // 2. Act（実行：リレーションの紐付け）
        // --------------------------------------------------
        // 各お問い合わせに対して、同じ $sharedTag を紐付ける（attach または sync）
        foreach ($contacts as $contact) {
            $contact->tags()->attach($tag->id);
        }

        // --------------------------------------------------
        // 3. Assert（検証）
        // --------------------------------------------------
        
        // ① 中間テーブル（contact_tag）に3件分の紐付けが存在するか検証
        foreach ($contacts as $contact) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id'     => $tag->id,
            ]);
        }

        // ② Tag モデル側から見て、紐付いている Contact が 3 件存在するか検証
        $tag->refresh(); // 最新状態に更新

        // リレーション経由で取得した件数が3件であること
        expect($tag->contacts)->hasCount(3);

        // 取得したお問い合わせのID一覧に、作成した3件のIDがすべて含まれていること
        $linkedContactIds = $tag->contacts->pluck('id')->toArray();
        foreach ($contacts as $contact) {
            expect($linkedContactIds)->toContain($contact->id);
        }
    }
}
