<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    //
    public function store(TagRequest $request)
    {
        $validated = $request->validated();

        $tag = Tag::create($validated);

        return redirect()->route('tags.store');
    }

    public function edit(Tag $tag)
    {
        return view('tags.edit', compact('tag'));
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $tag->update($request->validated());

        return redirect()->route('tags.update');
    }

    public function destroy()
    {

        $tag->delete();

        return redirect()->route('tags.destroy');
    }
}
