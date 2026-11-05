<?php

namespace App\Modules\Admissions\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Admissions\Actions\CreateDraftApplicationAction;
use App\Modules\Admissions\Actions\SubmitApplicationAction;
use App\Modules\Admissions\Actions\UpdateDraftApplicationAction;
use App\Modules\Admissions\Enums\DocType;
use App\Modules\Admissions\Models\AdmissionProgram;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Models\ApplicationDocument;
use App\Modules\Admissions\Rules\BdMobile;
use App\Modules\Admissions\Services\ApplicantAccountService;
use App\Modules\Admissions\Services\DocumentService;
use App\Modules\Admissions\Services\OtpService;
use App\Modules\Admissions\Services\QrTokenService;
use App\Modules\Admissions\Http\Controllers\Web\GuardianPortalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class ApplicantPortalController extends Controller
{
    private const VERIFIED_SESSION = 'admissions.verified_phone';
    private const TRACK_SESSION = 'admissions.track_application_id';
    private const PENDING_TRACK = 'admissions.pending_track';

    public function index()
    {
        $programs = AdmissionProgram::with(['academicYear', 'academicClass', 'academicGroup'])
            ->active()
            ->orderBy('section_type')
            ->orderBy('created_at')
            ->get();

        return view('admissions.portal.index', compact('programs'));
    }

    public function apply(string $programId)
    {
        $program = AdmissionProgram::with(['academicYear', 'academicClass', 'academicGroup'])->findOrFail($programId);

        if (!$program->isOpen()) {
            return redirect()->route('admissions.index')->with('error', 'This programme is closed or past its deadline.');
        }

        $verifiedPhone = session(self::VERIFIED_SESSION);

        return view('admissions.portal.apply', compact('program', 'verifiedPhone'));
    }

    /** Step 0: send a one-time code to the applicant's mobile. */
    public function sendCode(Request $request, OtpService $otp)
    {
        $request->validate(['phone' => ['required', new BdMobile]]);

        try {
            $code = $otp->generateOtp($request->phone, 'APPLICATION_AUTH');
        } catch (RuntimeException $e) {
            return back()->withErrors(['phone' => $e->getMessage()])->withInput();
        }

        // In local development the code is shown on screen. In production it would be sent by SMS (not yet connected).
        $flash = ['status' => 'A verification code was sent to your mobile.', 'otp_phone' => $request->phone];
        if (app()->environment('local') && config('admissions.otp_expose_debug_code')) {
            $flash['debug_code'] = $code;
        }

        return back()->with($flash);
    }

    public function verifyCode(Request $request, OtpService $otp)
    {
        $request->validate(['phone' => ['required', new BdMobile], 'code' => 'required|digits:6']);

        try {
            $otp->verifyOtp($request->phone, $request->code, 'APPLICATION_AUTH');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()])->withInput();
        }

        session([self::VERIFIED_SESSION => $request->phone]);

        return redirect()->back()->with('status', 'Mobile number verified. You can now fill the form.');
    }

    /**
     * Creates the draft, guardians, documents and submission in one transaction.
     * A failure at any step leaves nothing behind, so the applicant can try again.
     */
    public function store(
        Request $request,
        string $programId,
        CreateDraftApplicationAction $createDraft,
        UpdateDraftApplicationAction $updateDraft,
        SubmitApplicationAction $submit,
        DocumentService $documents,
        ApplicantAccountService $accounts
    ) {
        $program = AdmissionProgram::findOrFail($programId);

        $guardian = null;
        if ($guardianId = app(GuardianPortalController::class)->guardianId()) {
            // A signed-in guardian applies with their own account and their own phone number.
            $guardian = \App\Models\User::find($guardianId);
            if (!$guardian || (string) $request->phone !== (string) $guardian->phone_number) {
                return back()->withErrors(['phone' => 'Use the phone number you gave when you signed in.'])->withInput();
            }
        } elseif (session(self::VERIFIED_SESSION) !== $request->phone) {
            return back()->withErrors(['phone' => 'Verify this mobile number with the code first.'])->withInput();
        }

        $request->validate([
            'candidate_name' => 'required|string|max:255',
            'phone' => ['required', new BdMobile],
            'email' => 'nullable|email|max:255',
            'candidate_dob' => 'required|date|before:today',
            'gender' => 'required|string|in:MALE,FEMALE,OTHER',
            'blood_group' => 'nullable|string|max:10',
            'current_address' => 'required|string|max:500',
            'permanent_address' => 'nullable|string|max:500',
            'previous_school' => 'nullable|string|max:255',
            'previous_gpa' => 'nullable|numeric|between:0,5',
            // The guardian was already captured at sign-in; these are only asked when no guardian is signed in.
            ...($guardian ? [] : [
                'father_name' => 'required|string|max:255',
                'father_phone' => ['required', new BdMobile],
                'father_occupation' => 'nullable|string|max:100',
                'father_nid' => 'nullable|string|max:100',
                'mother_name' => 'nullable|string|max:255',
                'mother_phone' => ['nullable', new BdMobile],
                'mother_occupation' => 'nullable|string|max:100',
                'mother_nid' => 'nullable|string|max:100',
            ]),
            'photo' => 'nullable|file|max:1024',
            'birth_cert' => 'nullable|file|max:5120',
            'transcript' => 'nullable|file|max:5120',
            'declaration' => 'accepted',
        ]);

        try {
            $application = DB::transaction(function () use ($request, $program, $createDraft, $updateDraft, $submit, $documents, $accounts, $guardian) {
                $user = $guardian ?? $accounts->forMobile($request->phone, $request->candidate_name);

                $application = $createDraft->execute($program, $request->only([
                    'candidate_name', 'email', 'blood_group', 'current_address', 'permanent_address',
                    'previous_school', 'previous_gpa',
                ]) + ['phone' => $request->phone, 'candidate_dob' => $request->candidate_dob, 'gender' => $request->gender], $user);

                if ($guardian) {
                    // The signed-in guardian is the primary guardian; no need to ask again.
                    $guardians = [[
                        'relation' => session(GuardianPortalController::GUARDIAN_RELATION, 'GUARDIAN'),
                        'full_name' => $guardian->name, 'phone' => $guardian->phone_number, 'is_primary' => true,
                    ]];
                } else {
                $guardians = [[
                    'relation' => 'FATHER', 'full_name' => $request->father_name, 'phone' => $request->father_phone,
                    'occupation' => $request->father_occupation, 'nid_passport' => $request->father_nid, 'is_primary' => true,
                ]];
                if ($request->filled('mother_name')) {
                    $guardians[] = [
                        'relation' => 'MOTHER', 'full_name' => $request->mother_name,
                        'phone' => $request->mother_phone ?: $request->father_phone,
                        'occupation' => $request->mother_occupation, 'nid_passport' => $request->mother_nid, 'is_primary' => false,
                    ];
                }
                }
                $updateDraft->execute($application, ['guardians' => $guardians], $user);

                foreach (['photo' => DocType::PHOTO, 'birth_cert' => DocType::BIRTH_CERT, 'transcript' => DocType::PREVIOUS_TRANSCRIPT] as $field => $type) {
                    if ($request->hasFile($field)) {
                        $documents->store($application, $request->file($field), $type, $user);
                    }
                }

                $submit->execute($application, $user);

                return $application;
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput($request->except(['photo', 'birth_cert', 'transcript']));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()])->withInput($request->except(['photo', 'birth_cert', 'transcript']));
        }

        session([self::TRACK_SESSION => $application->id]);
        session()->forget(self::VERIFIED_SESSION);

        if ($guardian) {
            return redirect()->route('guardian.dashboard')
                ->with('success', "Application submitted. Your application number is {$application->application_no}.");
        }

        return redirect()->route('admissions.show', ['id' => $application->id])
            ->with('success', "Application submitted. Your application number is {$application->application_no}.");
    }

    /** Track page: shows the lookup form, or the verified application. */
    public function track(Request $request)
    {
        $application = null;
        if ($id = session(self::TRACK_SESSION)) {
            $application = Application::with(['program', 'documents', 'payments'])->find($id);
        }

        return view('admissions.portal.track', [
            'application' => $application,
            'pending' => session(self::PENDING_TRACK),
        ]);
    }

    /** Step 1 of tracking: match number + phone, then send a code. The reply is the same whether or not it matched. */
    public function trackSendCode(Request $request, OtpService $otp)
    {
        $request->validate(['application_number' => 'required|string|max:50', 'phone' => ['required', new BdMobile]]);

        $application = Application::where('application_no', trim($request->application_number))
            ->where('mobile', $request->phone)
            ->first();

        $flash = ['status' => 'If these details match an application, a code has been sent to the phone number.'];

        if ($application) {
            $code = $otp->generateOtp($request->phone, 'TRACK');
            session([self::PENDING_TRACK => ['application_id' => $application->id, 'phone' => $request->phone]]);

            if (app()->environment('local') && config('admissions.otp_expose_debug_code')) {
                $flash['debug_code'] = $code;
            }
        }

        return back()->with($flash);
    }

    public function trackVerify(Request $request, OtpService $otp)
    {
        $request->validate(['code' => 'required|digits:6']);

        $pending = session(self::PENDING_TRACK);
        if (!$pending) {
            return back()->withErrors(['code' => 'Start again: enter your application number and phone number.']);
        }

        try {
            $otp->verifyOtp($pending['phone'], $request->code, 'TRACK');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        session([self::TRACK_SESSION => $pending['application_id']]);
        session()->forget(self::PENDING_TRACK);

        return redirect()->route('admissions.track')->with('status', 'Verified. Your application is shown below.');
    }

    /** Application dashboard. Visible to the applicant whose session verified it, or to staff with permission. */
    public function show(Request $request, string $id)
    {
        $application = Application::with([
            'program.academicYear', 'program.academicClass', 'program.academicGroup',
            'guardians', 'documents', 'assessments', 'latestDecision', 'payments', 'statusHistory',
        ])->where('id', $id)->orWhere('application_no', $id)->firstOrFail();

        $isOwnerSession = session(self::TRACK_SESSION) === $application->id;
        $isStaff = $request->user()?->can('admission.application.view');
        $guardianId = session(GuardianPortalController::GUARDIAN_ID);
        $isOwnerGuardian = $guardianId && (string) $application->applicant_user_id === (string) $guardianId;

        if (!$isOwnerSession && !$isStaff && !$isOwnerGuardian) {
            abort(404);
        }

        // A guardian viewing their own application sees it inside the guardian sidebar.
        $layout = ($isOwnerGuardian && !$isOwnerSession && !$isStaff) ? 'admissions.layouts.guardian' : 'frontend.layouts.app';

        return view('admissions.portal.show', compact('application', 'layout'));
    }

    /** Signed link (10 minutes). The file is served from the private disk. */
    public function downloadDocument(Request $request, string $document)
    {
        $doc = ApplicationDocument::findOrFail($document);

        if (!Storage::disk($doc->storage_disk ?? config('admissions.document_disk', 'local'))->exists($doc->object_key)) {
            abort(404, 'File not found.');
        }

        return Storage::disk(config('admissions.document_disk', 'local'))->response(
            $doc->object_key,
            $doc->file_name,
            ['Content-Type' => $doc->mime_type]
        );
    }

    /**
     * Public check of an admission pass. It reveals only validity and the application number,
     * and it changes nothing. Scanning as staff uses POST /api/v1/admissions/qr/verify.
     */
    public function verifyQr(string $token, QrTokenService $qr)
    {
        try {
            $decoded = $qr->verifyToken($token);
            $application = Application::find($decoded['application_id']);
            $valid = $application && $application->qrIsValid() && $application->qr_token === $token;

            return view('admissions.portal.qr_verified', [
                'valid' => $valid,
                'applicationNumber' => $application?->application_no,
                'message' => $valid ? 'Valid admission pass.' : 'This pass is not valid for entry.',
            ]);
        } catch (InvalidArgumentException $e) {
            return view('admissions.portal.qr_verified', ['valid' => false, 'applicationNumber' => null, 'message' => 'This pass could not be verified.']);
        }
    }
}
