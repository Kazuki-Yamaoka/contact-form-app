<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;

class ContactController extends Controller
{
    public function index(IndexContactRequest $request)
    {
        $validated = $request->validated();

        $perPage = $request->input('per_page', 20);

        $query = Contact::with(['category', 'tags']);

        // 1. キーワード検索（名前・メールアドレス）
        if (! empty($validated['keyword'])) {
            $keyword = $validated['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        // 2. 性別フィルタ
        if (isset($validated['gender']) && $validated['gender'] != '0' && $validated['gender'] !== '') {
            $query->where('gender', $validated['gender']);
        }

        // 3. カテゴリフィルタ
        if (! empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // 4. 日付フィルタ（リクエストの 'date' を DB の 'created_at' にマッピング）
        if (! empty($validated['date'])) {
            $query->whereDate('created_at', $validated['date']);
        }

        $contacts = $query->latest()->paginate($perPage);

        return ContactResource::collection($contacts);
    }

    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();

        $contact = Contact::create($validated);

        if ($request->has('tag_ids')) {
            $contact->tags()->attach($request->input('tag_ids'));
        }

        $contact->load(['category', 'tags']);

        return (new ContactResource($contact))
            ->additional(['message' => 'お問い合わせを作成しました'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Contact $contact): ContactResource
    {
        $contact->load(['category', 'tags']);

        if (! $contact) {
            return response()->json([
                'message' => 'タスクが見つかりません',
                'error_code' => 'TASK_NOT_FOUND',
            ], 404);
        }

        return new ContactResource($contact);
    }

    public function update(UpdateContactRequest $request, Contact $contact)
    {
        if (! $contact) {
            return response()->json([
                'message' => 'タスクが見つかりません',
                'error_code' => 'TASK_NOT_FOUND',
            ], 404);
        }

        $validated = $request->validated();

        $contact->update($validated);

        $tagIds = $request->input('tags', []);

        $contact->tags()->sync($tagIds);

        $contact->load(['category', 'tags']);

        return (new ContactResource($contact))
            ->additional(['message' => 'お問い合わせを更新しました'])
            ->response();
    }

    public function destroy(Contact $contact)
    {
        $contact = Contact::with(['category', 'tags']);

        if (! $contact) {
            return response()->json([
                'message' => 'タスクが見つかりません',
                'error_code' => 'TASK_NOT_FOUND',
            ], 404);
        }

        $contact->delete();

        return response()->json(null, 204);
    }
}
