<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\AdminController;
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


// 仮ルート（CRUDで本実装に置き換え）
Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/contacts/{contact}', [AdminController::class, 'show'])->name('admin.show');
    Route::delete('/admin/contacts/{contact}', [AdminController::class, 'destroy'])->name('admin.destroy');
});

Route::get('/', [ContactController::class, 'index'])->name('contacts.index');
Route::get('/contacts', [ContactController::class, 'index']); // バリデーション失敗時

Route::post('/contacts/confirm', [ContactController::class, 'confirm'])->name('contacts.confirm');
Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
Route::get('/thanks', [ContactController::class, 'thanks'])->name('contacts.thanks');
