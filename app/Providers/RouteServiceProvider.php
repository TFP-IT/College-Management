<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));



            try {
                if (DB::connection()->getDatabaseName() != 'db_name' && Schema::hasTable('addons')) {
                    if (addon_status('hr_management') == 1) {
                        Route::middleware('web')
                            ->namespace($this->namespace)
                            ->group(base_path('routes/Addon/hrmanagement.php'));
                    }

                    if (addon_status('online_live_class') == 1) {
                        Route::middleware('web')
                            ->namespace($this->namespace)
                            ->group(base_path('routes/Addon/zoomliveclass.php'));
                    }

                    if (addon_status('payment_gateways') == 1) {
                        Route::middleware('web')
                            ->namespace($this->namespace)
                            ->group(base_path('routes/Addon/paymentgateways.php'));
                    }

                    if (addon_status('online_courses') == 1) {
                        Route::middleware('web')
                            ->namespace($this->namespace)
                            ->group(base_path('routes/Addon/onlineCourse.php'));
                    }

                    if (addon_status('inventory_manager') == 1) {
                        Route::middleware('web')
                            ->namespace($this->namespace)
                            ->group(base_path('routes/Addon/inventory_manager.php'));
                    }

                    if (addon_status('transport') == 1) {
                        Route::middleware('web')
                            ->namespace($this->namespace)
                            ->group(base_path('routes/Addon/transport.php'));
                    }

                    if (addon_status('alumni_manager') == 1) {
                        Route::middleware('web')
                            ->namespace($this->namespace)
                            ->group(base_path('routes/Addon/alumni_manager.php'));
                    }

                    if (addon_status('sms_center') == 1) {
                        Route::middleware('web')
                            ->namespace($this->namespace)
                            ->group(base_path('routes/Addon/sms_manager.php'));
                    }

                    if (addon_status('assignments') == 1) {
                        Route::middleware('web')
                            ->namespace($this->namespace)
                            ->group(base_path('routes/Addon/assignment-routes.php'));
                    }
                }
            } catch (\Exception $e) {
                // Skip addon route registration when database is unavailable during boot.
            }
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // Passwords: a few tries per account, and a wider cap per address (a campus shares one).
        RateLimiter::for('login', function (Request $request) {
            $account = mb_strtolower(trim((string) ($request->input('login_id') ?: $request->input('email'))));

            return [
                Limit::perMinute(5)->by('login:' . sha1($account . '|' . $request->ip())),
                Limit::perMinute(40)->by('login-ip:' . $request->ip()),
            ];
        });

        RateLimiter::for('signup', fn (Request $request) => Limit::perMinute(10)->by('signup:' . $request->ip()));

        RateLimiter::for('upload', fn (Request $request) => Limit::perMinute(20)->by('upload:' . $request->ip()));
    }
}
