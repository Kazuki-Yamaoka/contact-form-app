<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    //
    public function store(StoreTagRequest $request, Tag $Tag)
    {
        $validated = $request->validated();

        $tag = Tag::create($validated);

        return redirect()->route('admin.index');
    }

    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $tag->update($request->validated());

        return redirect()->route('admin.index')->with('success', 'タグを更新しました');
    }

    public function destroy(Tag $tag)
    {

        $tag->delete();

        return redirect()->route('admin.index')->with('success', 'タグを削除しました');
    }
}
