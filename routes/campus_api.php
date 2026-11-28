<?php

use App\Modules\Campus\Http\Controllers\Api\LifeController;
use App\Modules\Campus\Http\Controllers\Api\MeController;
use App\Modules\Campus\Http\Controllers\Api\ModuleController;
use App\Modules\Campus\Http\Controllers\Api\QuizController;
use App\Modules\Campus\Http\Controllers\Api\StudentController;
use App\Modules\Campus\Http\Controllers\Api\TeachingController;
use App\Modules\Campus\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

/*
| Campus API used by the mobile app. Student routes check on every request that the signed-in
| person may see that student (self, guardian, school office or the class teacher).
*/
Route::prefix('v1/campus')->name('campus.')->group(function () {

    // Signed link (no token), so a phone browser can open an attached photo or PDF.
    Route::get('attachments/{submission}', [MeController::class, 'attachment'])->name('attachment');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [MeController::class, 'show']);
        Route::get('notices', [MeController::class, 'notices']);
        Route::get('notifications', [MeController::class, 'notifications']);
        Route::post('notifications/read', [MeController::class, 'markRead']);

        Route::controller(StudentController::class)->prefix('students/{student}')->whereNumber('student')->group(function () {
            Route::get('today', 'today');
            Route::get('timetable', 'timetable');
            Route::get('attendance', 'attendance');
            Route::get('results', 'results');
            Route::get('subjects', 'subjects');
            Route::get('subjects/{subject}/lessons', 'lessons')->whereNumber('subject');
            Route::post('lessons/{lesson}/complete', 'completeLesson')->whereNumber('lesson');
            Route::get('assignments', 'assignments');
            Route::post('assignments/{assignment}/submit', 'submit')->whereNumber('assignment');
            Route::get('fees', 'fees');
            Route::post('invoices/{invoice}/pay', 'pay')->whereNumber('invoice')->middleware('throttle:10,1');
        });

        Route::controller(ModuleController::class)->group(function () {
            Route::get('modules', 'index');
            Route::get('records/{type}', 'records');
            Route::get('records/{type}/{record}', 'show')->whereNumber('record');
            Route::post('records/{type}', 'store')->middleware('throttle:20,1');
            Route::post('records/{type}/{record}', 'update')->whereNumber('record');
            Route::post('records/{type}/{record}/join', 'join')->whereNumber('record')->middleware('throttle:20,1');
        });

        Route::prefix('students/{student}')->whereNumber('student')->group(function () {
            Route::get('quizzes', [QuizController::class, 'index']);
            Route::post('quizzes/{quiz}/start', [QuizController::class, 'start'])->whereNumber('quiz');
            Route::post('quizzes/{quiz}/submit', [QuizController::class, 'submit'])->whereNumber('quiz');
            Route::get('quizzes/{quiz}/review', [QuizController::class, 'review'])->whereNumber('quiz');
            Route::controller(LifeController::class)->group(function () {
                Route::get('progress', 'progress');
                Route::get('calendar', 'calendar');
                Route::get('portfolio', 'portfolio');
                Route::get('contacts', 'contacts');
                Route::get('library', 'library');
                Route::post('library/{book}/reserve', 'reserve')->whereNumber('book');
                Route::get('elections', 'elections');
                Route::post('elections/{election}/nominate', 'nominate')->whereNumber('election');
                Route::post('elections/{election}/vote', 'vote')->whereNumber('election')->middleware('throttle:10,1');
            });
        });

        Route::controller(LifeController::class)->prefix('conversations')->group(function () {
            Route::get('/', 'conversations');
            Route::post('/', 'startConversation')->middleware('throttle:10,1');
            Route::get('{conversation}', 'showConversation')->whereNumber('conversation');
            Route::post('{conversation}', 'sendMessage')->whereNumber('conversation')->middleware('throttle:30,1');
        });

        Route::controller(TicketController::class)->prefix('tickets')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store')->middleware('throttle:10,1');
            Route::get('{ticket}', 'show')->whereNumber('ticket');
            Route::post('{ticket}/messages', 'reply')->whereNumber('ticket')->middleware('throttle:20,1');
        });

        Route::middleware('api.teacher')->controller(TeachingController::class)->prefix('teaching')->group(function () {
            Route::get('quizzes', [QuizController::class, 'teaching']);
            Route::post('quizzes', [QuizController::class, 'store']);
            Route::get('quizzes/{quiz}', [QuizController::class, 'results'])->whereNumber('quiz');
            Route::get('today', 'today');
            Route::get('classes', 'classes');
            Route::get('assignments', 'assignments');
            Route::post('assignments', 'createAssignment');
            Route::get('assignments/{assignment}/submissions', 'submissions')->whereNumber('assignment');
            Route::post('submissions/{submission}/grade', 'grade')->whereNumber('submission');
            Route::get('lessons', 'lessons');
            Route::post('lessons', 'createLesson');
            Route::get('exams', 'exams');
            Route::get('marks', 'marks');
            Route::post('marks', 'saveMarks');
            Route::post('results/submit', 'submitResults');
        });
    });
});
