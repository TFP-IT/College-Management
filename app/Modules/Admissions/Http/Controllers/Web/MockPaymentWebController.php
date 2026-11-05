<?php

namespace App\Modules\Admissions\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Admissions\Actions\InitiatePaymentAction;
use App\Modules\Admissions\Actions\ProcessPaymentCallbackAction;
use App\Modules\Admissions\Enums\PaymentType;
use App\Modules\Admissions\Models\AdmissionPayment;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Services\PaymentSignature;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MockPaymentWebController extends Controller
{
    private const TRACK_SESSION = 'admissions.track_application_id';

    /** The applicant's own session must hold this application (see ApplicantPortalController). */
    private function ownsApplication(string $applicationId): bool
    {
        return session(self::TRACK_SESSION) === $applicationId;
    }

    public function initiate(Request $request, string $applicationId, InitiatePaymentAction $action)
    {
        abort_unless($this->ownsApplication($applicationId), 404);

        $application = Application::findOrFail($applicationId);
        $type = PaymentType::from($request->query('type', 'APPLICATION_FEE'));
        $provider = $request->query('provider');

        try {
            $result = $action->execute($application, $type, $provider);
        } catch (ValidationException $e) {
            return redirect()->route('admissions.show', ['id' => $applicationId])->withErrors($e->errors());
        }

        return redirect()->away($result['redirect_url']);
    }

    /** Mock checkout page: choose success, failure or cancel. */
    public function checkout(string $paymentId)
    {
        $payment = AdmissionPayment::with('application.program')->findOrFail($paymentId);
        abort_unless($this->ownsApplication($payment->application_id), 404);

        return view('admissions.payments.mock', compact('payment'));
    }

    /**
     * The mock gateway's own server signs the outcome, then runs the same callback path as a real gateway.
     */
    public function process(Request $request, string $paymentId, ProcessPaymentCallbackAction $action)
    {
        $payment = AdmissionPayment::findOrFail($paymentId);
        abort_unless($this->ownsApplication($payment->application_id), 404);

        $choice = $request->input('action', 'success');
        $status = match ($choice) {
            'success' => 'SUCCESS',
            'cancel' => 'CANCELLED',
            default => 'FAILED',
        };

        $amount = number_format((float) $payment->amount, 2, '.', '');
        $data = [
            'transaction_ref' => $payment->transaction_ref,
            'status' => $status,
            'amount' => $amount,
            'currency' => $payment->currency,
            'signature' => PaymentSignature::sign($payment->transaction_ref, $status, $amount),
        ];

        try {
            $action->execute($payment, $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admissions.payments.callback', ['payment' => $payment->id, 'status' => strtolower($choice)]);
    }

    /**
     * Where the browser lands after a payment. It shows the status and changes nothing for the mock gateway.
     * For SSLCommerz the browser return is verified server-to-server before anything changes.
     */
    public function callback(Request $request, string $paymentId, ProcessPaymentCallbackAction $action)
    {
        $payment = AdmissionPayment::with('application.program')->findOrFail($paymentId);

        if ($payment->provider === 'sslcommerz' && $request->filled('val_id')) {
            try {
                $payment = $action->execute($payment, $request->all());
            } catch (ValidationException) {
                // Shown as "not confirmed" below; the IPN will also be checked.
            }
        }

        return view('admissions.payments.callback', ['payment' => $payment->fresh('application.program')]);
    }

    /** Server-to-server notification from SSLCommerz. Answers plain text. */
    public function ipn(Request $request, ProcessPaymentCallbackAction $action)
    {
        $payment = AdmissionPayment::where('transaction_ref', (string) $request->input('tran_id'))->first();

        if (!$payment || $payment->provider !== 'sslcommerz') {
            return response('Unknown transaction', 404);
        }

        try {
            $action->execute($payment, $request->all());
        } catch (ValidationException) {
            return response('Rejected', 422);
        }

        return response('OK', 200);
    }
}
