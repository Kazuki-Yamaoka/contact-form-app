<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\TagRequest;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Tag;

class ContactController extends Controller
{
    //
    public function index()
    {
        

        $categories = Category::all();

        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    public function confirm(StoreContactRequest $request)
    {
        $validated = $request->validated();

        $category = Category::find($validated['category_id']);

        // 3. 選択された Tags を取得（画面表示用）
        $tags = collect();
            if (!empty($validated['tag_ids'])) {
        $tags = Tag::whereIn('id', $validated['tag_ids'])->get();
    }

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    public function store()
    {
        return redirect()->route('contacts.thanks');
    }

    public function thanks()
    {
        return view('contact.thanks');
    }
}
