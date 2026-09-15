<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;

class ContactController extends Controller
{
    //
    public function index()
    {

        $categories = Category::all();

        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags')); // contacts？
    }

    public function confirm(StoreContactRequest $request)
    {
        // 入力値が存在しない・セッションが切れている場合は入力画面へリダイレクト
        if (! $request->has('email')) { // 例: 必須項目の有無で判定
            return redirect()->route('contacts.index');
        }

        $validated = $request->validated();

        $category = Category::find($validated['category_id']);

        // 3. 選択された Tags を取得（画面表示用）
        $tags = collect();
        if (! empty($validated['tag_ids'])) {
            $tags = Tag::whereIn('id', $validated['tag_ids'])->get();
        }

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();

        $contact = Contact::create($validated);

        // 選択されたタグ（中間テーブル contact_tag）を保
        // 存
        if (! empty($validated['tag_ids'])) {
            $contact->tags()->sync($validated['tag_ids']);
        }

        return redirect()->route('contacts.thanks');
    }

    public function thanks()
    {
        view('contact.thanks');
    }
}
