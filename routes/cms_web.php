<?php

use App\Modules\Cms\Http\Controllers\CmsAdminController;
use Illuminate\Support\Facades\Route;

/* CMS admin: requires login and the cms.manage permission. */
Route::prefix('admin/cms')->name('cms.admin.')->middleware(['auth', 'can:cms.manage'])->group(function () {
    Route::get('/', [CmsAdminController::class, 'index'])->name('index');

    Route::get('{key}', [CmsAdminController::class, 'list'])->where('key', '[a-z-]+')->name('list');
    Route::get('{key}/create', [CmsAdminController::class, 'create'])->where('key', '[a-z-]+')->name('create');
    Route::post('{key}', [CmsAdminController::class, 'store'])->where('key', '[a-z-]+')->name('store');
    Route::get('{key}/{id}/edit', [CmsAdminController::class, 'edit'])->where('key', '[a-z-]+')->name('edit');
    Route::put('{key}/{id}', [CmsAdminController::class, 'update'])->where('key', '[a-z-]+')->name('update');
    Route::delete('{key}/{id}', [CmsAdminController::class, 'destroy'])->where('key', '[a-z-]+')->name('destroy');
});
