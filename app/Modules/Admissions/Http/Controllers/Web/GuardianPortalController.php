<?php

namespace App\Modules\Admissions\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\GuardianOtpMail;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Rules\BdMobile;
use App\Modules\Admissions\Services\GuardianAccountService;
use App\Modules\Admissions\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use RuntimeException;

/**
 * Guardian sign-in by email code, then a sidebar portal for the guardian's applications.
 */
class GuardianPortalController extends Controller
{
    public const GUARDIAN_ID = 'admissions.guardian_id';
    public const GUARDIAN_NAME = 'admissions.guardian_name';
    public const GUARDIAN_PROGRAM = 'admissions.guardian_program';
    public const GUARDIAN_RELATION = 'admissions.guardian_relation';
    private const PENDING = 'admissions.guardian_pending';

    /** Step 1 and 2 on one page: the guardian's details, then the OTP entry once a code is sent. */
    public function start(Request $request, AdmissionProgram $program)
    {
        if (!$program->isOpen()) {
            return redirect()->route('admissions.index')->with('error', 'This programme is closed or past its deadline.');
        }

        // A guardian who is already signed in does not give their details again.
        if ($this->guardianId()) {
            return redirect()->route('guardian.apply', ['program' => $program->id]);
        }

        if ($request->boolean('reset')) {
            session()->forget(self::PENDING);
        }

        $pending = session(self::PENDING);
        if (!$pending || (string) ($pending['program_id'] ?? '') !== (string) $program->id) {
            $pending = null;
        }

        return view('admissions.guardian.start', compact('program', 'pending'));
    }

    /** Step 2: store the details in the session and email a code. */
    public function sendCode(Request $request, AdmissionProgram $program, OtpService $otp)
    {
        $data = $request->validate([
            'guardian_name' => 'required|string|max:255',
            'relation' => 'required|in:FATHER,MOTHER,GUARDIAN',
            'email' => 'required|email|max:255',
            'phone' => ['required', new BdMobile],
        ]);
        $email = strtolower(trim($data['email']));

        try {
            $code = $otp->generateOtp($email, 'GUARDIAN_AUTH');
        } catch (RuntimeException $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput();
        }

        $debug = app()->environment('local') && config('admissions.otp_expose_debug_code');

        try {
            Mail::to($email)->send(new GuardianOtpMail($code, $data['guardian_name']));
        } catch (\Throwable $e) {
            report($e);
            if (!$debug) {
                return back()->withErrors(['email' => 'We could not send the code to your email. Please try again later.'])->withInput();
            }
        }

        session([self::PENDING => $data + ['email' => $email, 'program_id' => $program->id]]);

        $flash = ['status' => "An OTP was sent to {$email}."];
        if ($debug) {
            $flash['debug_code'] = $code;
        }

        return redirect()->route('admissions.guardian.start', ['program' => $program->id])->with($flash);
    }

    /** Step 3: check the OTP, store the guardian, and open the portal. */
    public function verify(Request $request, OtpService $otp, GuardianAccountService $accounts)
    {
        $request->validate(['code' => 'required|digits:6']);

        $pending = session(self::PENDING);
        if (!$pending) {
            return redirect()->route('admissions.index')->with('error', 'Please start again.');
        }

        try {
            $otp->verifyOtp($pending['email'], $request->code, 'GUARDIAN_AUTH');
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admissions.guardian.start', ['program' => $pending['program_id']])
                ->withErrors(['code' => $e->getMessage()]);
        }

        $guardian = $accounts->forEmail($pending['email'], $pending['guardian_name'], $pending['phone']);

        session([
            self::GUARDIAN_ID => $guardian->id,
            self::GUARDIAN_NAME => $pending['guardian_name'],
            self::GUARDIAN_PROGRAM => $pending['program_id'],
            self::GUARDIAN_RELATION => $pending['relation'],
        ]);
        session()->forget(self::PENDING);

        return redirect()->route('guardian.dashboard')->with('status', 'Signed in. Welcome, ' . $pending['guardian_name'] . '.');
    }

    /** The guardian's sidebar portal: their applications and a shortcut to apply. */
    public function dashboard()
    {
        $guardianId = $this->guardianId();
        if (!$guardianId) {
            return redirect()->route('admissions.index')->with('error', 'Please sign in with your email first.');
        }

        $applications = Application::with('program')
            ->where('applicant_user_id', $guardianId)
            ->latest()
            ->get();

        $programId = session(self::GUARDIAN_PROGRAM);
        $program = $programId ? AdmissionProgram::find($programId) : null;

        return view('admissions.guardian.dashboard', [
            'name' => session(self::GUARDIAN_NAME),
            'applications' => $applications,
            'program' => $program?->isOpen() ? $program : null,
        ]);
    }

    /** Apply form, for a signed-in guardian. The candidate's phone is the guardian's phone. */
    public function apply(string $programId)
    {
        $guardianId = $this->guardianId();
        if (!$guardianId) {
            return redirect()->route('admissions.index')->with('error', 'Please sign in with your email first.');
        }

        $program = AdmissionProgram::with(['academicYear', 'academicClass', 'academicGroup'])->findOrFail($programId);
        if (!$program->isOpen()) {
            return redirect()->route('guardian.dashboard')->with('error', 'This programme is closed or past its deadline.');
        }

        $guardian = \App\Models\User::find($guardianId);

        return view('admissions.portal.apply', [
            'program' => $program,
            'verifiedPhone' => $guardian?->phone_number,
            'layout' => 'admissions.layouts.guardian',
            'guardianMode' => true,
            'guardian' => [
                'name' => $guardian?->name,
                'phone' => $guardian?->phone_number,
                'relation' => session(self::GUARDIAN_RELATION, 'GUARDIAN'),
            ],
        ]);
    }

    public function logout()
    {
        session()->forget([self::GUARDIAN_ID, self::GUARDIAN_NAME, self::GUARDIAN_PROGRAM, self::GUARDIAN_RELATION]);

        return redirect()->route('admissions.index')->with('status', 'You have signed out.');
    }

    /** The signed-in guardian's id, or null. A session whose account no longer exists is signed out. */
    public function guardianId(): ?string
    {
        $id = session(self::GUARDIAN_ID);

        if ($id && !\App\Models\User::whereKey($id)->exists()) {
            session()->forget([self::GUARDIAN_ID, self::GUARDIAN_NAME, self::GUARDIAN_PROGRAM, self::GUARDIAN_RELATION]);

            return null;
        }

        return $id;
    }
}
