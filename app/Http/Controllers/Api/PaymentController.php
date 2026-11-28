<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\PaymentSettlement;
use App\Services\Payments\SslCommerzClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(private SslCommerzClient $gateway, private PaymentSettlement $settlement)
    {
    }

    /** Play Store builds call the live API, so live callbacks must return there. */
    private function appUrl(string $path): string
    {
        $callbackRoot = rtrim((string) (config('services.sslcommerz.callback_url') ?: 'https://backendmcc.tocc.world'), '/');
        $root = $this->gateway->isSandbox() ? rtrim(url('/'), '/') : $callbackRoot;

        return $root . $path;
    }

    public function initiatePayment(Request $request)
    {
        // Older app builds still send "amount". It is deliberately not read.
        $request->validate([
            'application_no' => 'required|string',
            'fee_type' => 'nullable|in:application_fee,admission_fee',
        ]);

        $admission = Admission::where('application_no', $request->application_no)->first();

        // Someone else's application looks the same as a missing one, so numbers cannot be probed.
        if (!$admission || !$admission->isOwnedBy($request->user())) {
            return response()->json(['status' => 'error', 'message' => 'Admission application not found.'], 404);
        }

        $feeType = $request->fee_type ?: 'application_fee';

        if ($admission->status === 'rejected') {
            return response()->json(['status' => 'error', 'message' => 'This application was not accepted, so no fee is due.'], 400);
        }

        // Application fee can only be paid when pending/under_review. Once approved, application fee is already verified.
        if ($feeType === 'application_fee' && $admission->status === 'approved') {
            return response()->json(['status' => 'error', 'message' => 'Application is already approved.'], 400);
        }

        if ($admission->payments()->where('type', $feeType)->where('status', 'paid')->exists()) {
            return response()->json(['status' => 'error', 'message' => 'This fee is already paid.'], 409);
        }

        if (!$this->gateway->isConfigured()) {
            Log::error('SSLCommerz credentials are missing. Set SSL_STORE_ID and SSL_STORE_PASSWORD.');

            return response()->json(['status' => 'error', 'message' => 'Online payment is not available right now.'], 503);
        }

        $amount = (float) config('fees.' . $feeType);
        $transactionId = ($feeType === 'admission_fee' ? 'MCC_ENR_' : 'MCC_ADM_') . Str::lower(Str::random(20));

        $student = User::find($admission->user_id);

        $transaction = PaymentTransaction::create([
            'payable_type' => get_class($admission),
            'payable_id' => $admission->id,
            'user_id' => $admission->user_id,
            'type' => $feeType,
            'amount' => $amount,
            'application_fee' => ($feeType === 'application_fee') ? $amount : null,
            'admission_fee' => ($feeType === 'admission_fee') ? $amount : null,
            'currency' => 'BDT',
            'transaction_id' => $transactionId,
            'application_no' => $admission->application_no,
            'student_id' => $student?->student_id,
            'student_name' => $admission->name,
            'class_name' => $admission->class_name,
            'status' => 'pending',
        ]);

        $postData = [
            'total_amount' => $amount,
            'currency' => 'BDT',
            'tran_id' => $transactionId,
            'success_url' => $this->appUrl('/api/payment/success'),
            'fail_url' => $this->appUrl('/api/payment/fail'),
            'cancel_url' => $this->appUrl('/api/payment/cancel'),
            'ipn_url' => $this->appUrl('/api/payment/ipn'),
            'cus_name' => $admission->name,
            'cus_email' => $admission->email ?? 'student@maestrocrown.edu',
            'cus_add1' => $admission->present_address ?? 'Dhaka',
            'cus_add2' => 'Dhaka',
            'cus_city' => 'Dhaka',
            'cus_state' => 'Dhaka',
            'cus_postcode' => '1000',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $admission->mobile,
            'cus_fax' => '01711111111',
            'shipping_method' => 'NO',
            'num_of_item' => 1,
            'product_name' => ($feeType === 'admission_fee') ? 'Admission Fee' : 'Application Fee',
            'product_category' => 'Education',
            'product_profile' => 'general',
            'value_a' => $transaction->id,
            'value_b' => $feeType,
        ];

        try {
            $response = $this->gateway->createSession($postData);
        } catch (ConnectionException $e) {
            $transaction->status = 'failed';
            $transaction->save();
            Log::warning('SSLCommerz session request timed out', ['transaction_id' => $transactionId]);

            return response()->json([
                'status' => 'error',
                'message' => 'SSLCommerz did not respond. Please try the payment again.',
            ], 504);
        }
        $result = $response->json();

        if (isset($result['status']) && $result['status'] === 'SUCCESS') {
            return response()->json([
                'status' => 'success',
                'gateway_url' => $result['GatewayPageURL']
            ]);
        }

        $failedReason = is_array($result) ? ($result['failedreason'] ?? null) : null;
        $transaction->status = 'failed';
        $transaction->gateway_response = is_array($result) ? $result : ['raw' => $response->body()];
        $transaction->save();

        Log::warning('SSLCommerz session initiation refused', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'sandbox' => $this->gateway->isSandbox(),
            'failedreason' => $failedReason,
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $failedReason ?: 'Failed to initiate payment gateway.',
        ], 500);
    }

    public function paymentSuccess(Request $request)
    {
        $valId = (string) $request->input('val_id');
        $tranId = (string) $request->input('tran_id');

        $paid = $valId !== '' && $tranId !== ''
            && $this->settlement->settle($tranId, $valId) === PaymentSettlement::PAID;

        return redirect($this->appUrl('/api/payment/result?status=' . ($paid ? 'success' : 'fail')));
    }

    /** Server-to-server notice. Same validation as the customer redirect, with no page. */
    public function paymentIpn(Request $request)
    {
        $valId = (string) $request->input('val_id');
        $tranId = (string) $request->input('tran_id');
        if ($valId === '' || $tranId === '') {
            return response('FAILED', 400);
        }

        return match ($this->settlement->settle($tranId, $valId)) {
            PaymentSettlement::PAID => response('OK', 200),
            PaymentSettlement::HELD => response('HOLD', 200),
            PaymentSettlement::UNREACHABLE => response('FAILED', 504),
            default => response('FAILED', 200),
        };
    }

    public function paymentFail(Request $request)
    {
        $this->markFailed((string) $request->input('tran_id'));

        return redirect($this->appUrl('/api/payment/result?status=fail'));
    }

    public function paymentCancel(Request $request)
    {
        $this->markFailed((string) $request->input('tran_id'));

        return redirect($this->appUrl('/api/payment/result?status=cancel'));
    }

    /** Only an open attempt can be closed this way. A later verified payment still settles it. */
    private function markFailed(string $tranId): void
    {
        PaymentTransaction::where('transaction_id', $tranId)
            ->where('status', 'pending')
            ->update(['status' => 'failed']);
    }
}
