<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\StoreTagRequest;
use App\Models\Contact;
use App\Models\Category;

class ContactController extends Controller
{
    //
    public function index(CategoryRequest $request, TagRequest $request)
    {
        $categories = Category::all($request->validated());

        $tags = Tag::all($request->validated());

        return view('contact._form', compact('categories', 'tags'));
    }

    public function confirm(CategoryRequest $request, TagRequest $request)
    {
        $categories = Category::find($request->validated());

        $tags = Tag::find($request->validated());

        return view('contact.confirm', compact('categories', 'tags'));
    }

    public function store()
    {
        return redirect()->route('contact.thanks');
    }

    public function thanks()
    {
        return view('contact.thanks');
    }
}
