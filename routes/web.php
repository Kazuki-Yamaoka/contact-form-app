<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// 仮ルート（CRUDで本実装に置き換え）
Route::middleware('auth')->group(function () {
    Route::get('/admin', fn() => 'お問い合わせ一覧（準備中）')->name('admin.index');    
});

Route::get('/contacts', fn() => 'お問い合わせフォーム（準備中）')->name('contacts.index');