<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Contact;
use Illuminate\Http\Request;


class AdminController extends Controller
{
    //
    public function index(IndexContactRequest $request)
    {
        $validated = $request->validated();

        $query = Contact::with(['category', 'tags']);

        if (!empty($validated['keyword'])) {
            $keyword = $validated['keyword'];

        $query->where(function ($q) use ($keyword) {
                $q->where('gender', 'like', "%{$keyword}%")
                  ->orWhere('category_id', 'like', "%{$keyword}%")
                  ->orWhere('date', 'like', "%{$keyword}%");
            });
        }

        $contacts = $query->latest()->paginate(7);

        $categories = Category::all();

        $tags = Tag::all();

        return view('admin.index', compact('contacts', 'categories','tags'));
    }

    public function show(Contact $contact)
    {
        $contact->load('category','tags');

        return view('admin.show', compact('contact'));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete(); 

        return redirect()->route('admin.index');
    }

    public function export(ExportContactRequest $request)
    {
        $validated = $request->validated();
        // 1. 検索クエリの構築 (index メソッドと同じ絞り込み条件を適用)
        $query = Contact::with(['category', 'tags']);

        if (!empty($validated['keyword'])) {
            $keyword = validated['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                  ->orWhere('last_name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if (!empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (!empty($validated['gender'])) {
            $query->where('gender', $validated['gender']);
        }

        // 2. ファイル名とレスポンスヘッダーの設定
        $fileName = 'contacts_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        // 3. StreamedResponse による大容量対応ダウンロード
        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // ★ Excel 文字化け対策: BOM (Byte Order Mark) の書き込み
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV ヘッダー行の書き込み
            fputcsv($handle, [
                'ID',
                'お名前',
                '性別',
                'メールアドレス',
                '電話番号',
                '住所',
                '建物名',
                'カテゴリ',
                'タグ',
                'お問い合わせ内容',
                '登録日時',
            ]);

            // メモリ枯渇を防ぐため chunk / cursor を使用して順次出力
            $query->orderby('created_at', 'desc')->chunk(100, function ($contacts) use ($handle) {
                foreach ($contacts as $contact) {
                    // 性別表記の変換例
                    $genderText = match ((int)$contact->gender) {
                        1 => '男性',
                        2 => '女性',
                        3 => 'その他',
                        default => '不明',
                    };

                    // 紐付くタグ名をカンマ区切りで結合
                    $tagNames = $contact->tags->pluck('name')->implode(', ');

                    fputcsv($handle, [
                        $contact->id,
                        $contact->last_name . ' ' . $contact->first_name,
                        $genderText,
                        $contact->email,
                        $contact->tel,
                        $contact->address,
                        $contact->building,
                        $contact->category?->name ?? '',
                        $tagNames,
                        $contact->detail,
                        $contact->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
        


    }
}
