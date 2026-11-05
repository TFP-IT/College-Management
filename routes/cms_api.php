<?php

use App\Modules\Cms\Http\Controllers\CmsApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/cms')->name('api.v1.cms.')->middleware('throttle:60,1')->group(function () {
    Route::get('notices', [CmsApiController::class, 'notices'])->name('notices');
    Route::get('gallery', [CmsApiController::class, 'gallery'])->name('gallery');
    Route::get('staff', [CmsApiController::class, 'staff'])->name('staff');
    Route::get('pages/{slug}', [CmsApiController::class, 'page'])->name('pages');
});
