<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\RejectDangerousUploads::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \App\Http\Middleware\RejectDangerousUploads::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * Middleware aliases that can be assigned to groups or single routes.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
        'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'role_id' => \App\Http\Middleware\RoleId::class,
        'superAdmin' => \App\Http\Middleware\SuperAdminMiddleware::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'student' => \App\Http\Middleware\StudentMiddleware::class,
        'parent' => \App\Http\Middleware\ParentMiddleware::class,
        'accountant' => \App\Http\Middleware\AccountantMiddleware::class,
        'librarian' => \App\Http\Middleware\LibrarianMiddleware::class,
        'teacher' => \App\Http\Middleware\TeacherMiddleware::class,
        'api.teacher' => \App\Http\Middleware\EnsureApiTeacher::class,
        'api.student' => \App\Http\Middleware\EnsureApiStudent::class,
        'academic.admin' => \App\Http\Middleware\EnsureAcademicAdmin::class,
        'is_installed' => \App\Http\Middleware\IsInstalled::class,
        'alumni' => \App\Http\Middleware\AlumniMiddleware::class,
        'admin_permission' => \App\Http\Middleware\AdminPermission::class,
        'installer' => \App\Http\Middleware\EnsureInstallerAllowed::class,
        'updater' => \App\Http\Middleware\EnsureUpdaterAllowed::class,
        'own.child' => \App\Http\Middleware\EnsureOwnChild::class,
        'own.thread' => \App\Http\Middleware\EnsureThreadParticipant::class,
        'staff' => \App\Http\Middleware\EnsureStaff::class,
        'office' => \App\Http\Middleware\EnsureOffice::class,
    ];
}
