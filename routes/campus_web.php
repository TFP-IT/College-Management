<?php

use App\Modules\Campus\Http\Controllers\Web\CampusOfficeController;
use App\Modules\Campus\Http\Controllers\Web\DocumentController;
use App\Modules\Campus\Http\Controllers\Web\ModuleOfficeController;
use App\Modules\Campus\Http\Controllers\Web\OfficeController;
use Illuminate\Support\Facades\Route;

/* Anyone holding a printed receipt can check it. The signature stops receipt numbers being guessed. */
Route::get('verify/receipt/{receipt}', [OfficeController::class, 'verifyReceipt'])->name('receipt.verify')->middleware(['signed:relative', 'throttle:30,1']);

/* Marksheet and admit card, opened from an expiring signed link. */
Route::prefix('documents')->name('document.')->middleware(['signed:relative', 'throttle:30,1'])->group(function () {
    Route::get('marksheet/{student}/{publication}', [DocumentController::class, 'marksheet'])->name('marksheet')->whereNumber(['student', 'publication']);
    Route::get('admit-card/{student}/{category}', [DocumentController::class, 'admitCard'])->name('admit')->whereNumber(['student', 'category']);
});

/* Office portal. Each area checks the signed-in role (see OfficeAccess). */
Route::prefix('office')->name('office.')->middleware('auth')->controller(OfficeController::class)->group(function () {
    // Accounts without the dashboard are sent to the first page they can open.
    Route::get('/', 'dashboard')->name('dashboard');

    Route::middleware('office:students')->group(function () {
        Route::get('students', 'students')->name('students');
        Route::get('students/{student}', 'showStudent')->name('student')->whereNumber('student');
    });

    Route::middleware('office:fees')->group(function () {
        Route::get('fees', 'fees')->name('fees');
        Route::get('fees/export', 'export')->name('fees.export');
        Route::post('fees/bill', 'bill')->name('fees.bill');
        Route::post('fees/{invoice}/collect', 'collect')->name('fees.collect')->whereNumber('invoice');
        Route::get('receipts/{payment}', 'receipt')->name('receipt')->whereNumber('payment');
    });

    Route::middleware('office:results')->group(function () {
        Route::get('results', 'results')->name('results');
        Route::post('results/open', 'openResult')->name('result.open');
        Route::get('results/{publication}', 'showResult')->name('result')->whereNumber('publication');
        Route::post('results/{publication}/publish', 'publishResult')->name('result.publish')->whereNumber('publication');
        Route::post('results/{publication}/return', 'returnResult')->name('result.return')->whereNumber('publication');
    });

    Route::middleware('office:announcements')->group(function () {
        Route::get('announcements', 'announcements')->name('announcements');
        Route::post('announcements', 'announce')->name('announce');
    });

    Route::middleware('office:help')->group(function () {
        Route::get('help', 'tickets')->name('tickets');
        Route::get('help/{ticket}', 'showTicket')->name('ticket')->whereNumber('ticket');
        Route::post('help/{ticket}', 'replyTicket')->name('ticket.reply')->whereNumber('ticket');
    });
});

Route::prefix('office')->name('office.')->middleware('auth')->group(function () {
    Route::middleware('office:modules')->group(function () {
        Route::get('modules', [ModuleOfficeController::class, 'directory'])->name('modules');
        Route::get('m/{type}', [ModuleOfficeController::class, 'index'])->name('module');
        Route::get('m/{type}/new', [ModuleOfficeController::class, 'create'])->name('module.create');
        Route::post('m/{type}', [ModuleOfficeController::class, 'store'])->name('module.store');
        Route::post('m/{type}/{record}', [ModuleOfficeController::class, 'update'])->name('module.update')->whereNumber('record');
    });
    Route::middleware('office:approvals')->group(function () {
        Route::get('m/{type}/{record}', [ModuleOfficeController::class, 'show'])->name('module.show')->whereNumber('record');
        Route::get('approvals', [ModuleOfficeController::class, 'approvals'])->name('approvals');
        Route::post('approvals/{approval}', [ModuleOfficeController::class, 'decide'])->name('approvals.decide')->whereNumber('approval');
    });

    Route::middleware('office:library')->group(function () {
        Route::get('library', [CampusOfficeController::class, 'library'])->name('library');
        Route::post('library/books', [CampusOfficeController::class, 'addBook'])->name('library.book');
        Route::post('library/issue', [CampusOfficeController::class, 'issueBook'])->name('library.issue');
        Route::post('library/loans/{loan}', [CampusOfficeController::class, 'closeLoan'])->name('library.close')->whereNumber('loan');
    });
    Route::middleware('office:elections')->group(function () {
        Route::get('elections', [CampusOfficeController::class, 'elections'])->name('elections');
        Route::post('elections', [CampusOfficeController::class, 'createElection'])->name('elections.create');
        Route::post('elections/{election}/candidates/{candidate}', [CampusOfficeController::class, 'reviewCandidate'])->name('elections.candidate')->whereNumber(['election', 'candidate']);
        Route::post('elections/{election}/publish', [CampusOfficeController::class, 'publishElection'])->name('elections.publish')->whereNumber('election');
    });
    Route::get('insights', [CampusOfficeController::class, 'insights'])->name('insights')->middleware('office:insights');
    Route::get('audit', [CampusOfficeController::class, 'audit'])->name('audit')->middleware('office:audit');
    Route::middleware('office:messages')->group(function () {
        Route::get('messages/{conversation?}', [CampusOfficeController::class, 'messages'])->name('messages')->whereNumber('conversation');
        Route::post('messages/{conversation}', [CampusOfficeController::class, 'reply'])->name('messages.reply')->whereNumber('conversation');
    });
});

Route::get('verify/certificate/{code}', [ModuleOfficeController::class, 'verifyCertificate'])->name('certificate.verify')->middleware('throttle:30,1');
