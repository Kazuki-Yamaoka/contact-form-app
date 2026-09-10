<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\ContactRequest;
use App\Models\Task;
use App\Models\Contact;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        // return Contact::with(['category', 'tags'])->get();
         
        $perPage = $request->input('per_page', 20);

        $query = Contact::with(['category', 'tags']);

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

        $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                  ->orWhere('last_name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $contacts = $query->latest()->paginate($perPage);

        return ContactResource::collection($contacts);
    }
        
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContactRequest $request)
    {
        //
        $validated = $request->validated();

        $contact = Contact::create($validated);

        if ($request->has('tag_ids')) {
        // $request->input('tag_ids') を使って送信された配列 [1, 2] を取得して結びつける
            $contact->tags()->attach($request->input('tag_ids'));
    }

        // 4. 表示用にリレーション（category, tags）をロード
        $contact->load(['category', 'tags']);

        return (new ContactResource($contact))
            ->additional(['message' => 'お問い合わせを更新しました'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact): ContactResource
    {
        //
        $contact->load(['category', 'tags']);

        /*
        // お問い合わせが見つからない場合
        if (!$contact) {
            return response()->json([
                'message' => 'お問い合わせが見つかりませんでした。'
            ], 404);
        }
        */

        return new ContactResource($contact);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContactRequest $request, Contact $contact)
    {
        //
        $validated = $request->validated();

        $contact = Contact::update($validated);

        $contact->tags()->sync($tagIds);

        $contact->load(['category', 'tags']);

        /*
        if(!$contact) {
            return response()->json([
                'message' => 'お問い合わせが見つかりませんでした。',
            ], 404);
        }
        */

        return (new ContactResource($contact))
            ->additional(['message' => 'お問い合わせを更新しました'])
            ->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
        //
        $contact = Contact::with(['category', 'tags']);

        /*
        // Laravel の「Implicit Binding（ルートモデル結合）」を使っているため、
        // $contact が見つからない場合は自動で 404 が返ります。
        // そのため if (!$contact) の判定は不要です。
        if(!$contact) {
            return response()->json([
                'message' => 'お問い合わせが見つかりません',
            ], 404);
        }
        */ 

        $contact->delete();

        return response()->json(null, 204);
    }
}
