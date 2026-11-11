<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\Admission;
use App\Models\User;
use App\Services\WebAccountSync;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        // Account creation in the app must never fail because of the web sync.
        $sync = function (callable $task) {
            try {
                $task(app(WebAccountSync::class));
            } catch (\Throwable $e) {
                report($e);
            }
        };
        User::saved(fn (User $user) => $sync(fn ($s) => $s->syncUser($user)));
        Admission::saved(fn (Admission $admission) => $sync(fn ($s) => $s->syncAdmission($admission)));
    }
}
