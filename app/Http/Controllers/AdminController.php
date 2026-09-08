<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Contact;
use Illuminate\Http\Request;


class AdminController extends Controller
{
    //
    public function index()
    {

$contacts = Contact::with(['categories', 'tags'])
                    ->paginate(7);

        $categories = Category::all();

        return view('admin.index', compact('contacts', 'categories'));
    }

    public function show(Contact $contact)
    {
        $contact->load('categories','tags');

        return view('admin.show', compact('contact'));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete(); 

        return redirect()->route('admin.index');
    }
}
