<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('throttle:login')->only('login');
    }

    public function login(Request $request)
    {
        $input = $request->all();

        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (auth()->attempt(array('email' => $input['email'], 'password' => $input['password']), $request->boolean('remember'))) {

            // A new session id after sign-in, so one planted earlier is worthless.
            $request->session()->regenerate();

            $user = auth()->user();

            if ($user->role_id != 1 && $user->role_id != 2) {
                if (school_status_check($user->email) != 1) {
                    auth()->logout();
                    return redirect()->route('login')
                        ->with('error', 'Your school is yet to be authorized to this service!');
                }
            }

            if (!in_array((int) $user->role_id, [1, 2], true) && $user->getRoleNames()->intersect(\App\Console\Commands\GrantAdmissionsRole::OFFICE_ROLES)->isNotEmpty()) {
                return redirect()->route('office.dashboard');
            }

            if ($user->role_id == 1) {

                session(['superadmin_login' => 1]);
                return redirect()->route('superadmin.dashboard');

            } else if ($user->role_id == 2) {

                session(['admin_login' => 2]);
                return redirect()->route('admin.dashboard');

            } else if ($user->role_id == 3) {

                session(['teacher_login' => 3]);
                return redirect()->route('teacher.dashboard');

            } else if ($user->role_id == 4) {

                session(['accountant_login' => 4]);
                return redirect()->route('accountant.dashboard');

            } else if ($user->role_id == 5) {

                session(['librarian_login' => 5]);
                return redirect()->route('librarian.dashboard');

            } else if ($user->role_id == 6) {

                session(['parent_login' => 6]);
                return redirect()->route('parent.dashboard');

            } else if ($user->role_id == 7) {

                session(['student_login' => 7]);
                return redirect()->route('student.dashboard');

            } else if ($user->role_id == 8) {

                session(['driver_login' => 8]);
                return redirect()->route('driver.dashboard');

            } else if ($user->role_id == 9) {

                session(['alumni_login' => 9]);
                return redirect()->route('alumni.dashboard');

            } else {
                return redirect()->route('home');
            }
        } else {
            return redirect()->route('login')
                ->with('error', 'Email-Address And Password Are Wrong.');
        }
    }
}
