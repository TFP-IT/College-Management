<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Admission;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $storeId;
    private $storePass;
    private $isSandbox;

    public function __construct()
    {
        $this->storeId = env('SSL_STORE_ID', 'versa6aa14fffc6aef');
        $this->storePass = env('SSL_STORE_PASSWORD', 'versa6aa14fffc6aef@ssl');
        $this->isSandbox = env('SSL_IS_SANDBOX', true);
    }

    private function getBaseUrl()
    {
        return $this->isSandbox ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';
    }

    public function initiatePayment(Request $request)
    {
        $request->validate([
            'application_no' => 'required|string',
            'amount' => 'nullable|numeric|min:1',
            'fee_type' => 'nullable|string', // 'admission_fee' or 'application_fee'
        ]);

        $admission = Admission::where('application_no', $request->application_no)->first();

        if (!$admission) {
            return response()->json(['status' => 'error', 'message' => 'Admission application not found.'], 404);
        }

        $feeType = $request->fee_type ?: 'application_fee';

        // Application fee can only be paid when pending/under_review. Once approved, application fee is already verified.
        if ($feeType === 'application_fee' && $admission->status === 'approved') {
            return response()->json(['status' => 'error', 'message' => 'Application is already approved.'], 400);
        }

        // Admission fee can be paid once approved or anytime during enrollment.
        $defaultAmount = ($feeType === 'admission_fee') ? 3000.00 : 500.00;
        $amount = $request->filled('amount') && floatval($request->amount) > 0 
            ? floatval($request->amount) 
            : $defaultAmount;

        $transactionId = ($feeType === 'admission_fee' ? 'MCC_ENR_' : 'MCC_ADM_') . uniqid();

        $student = \App\Models\User::find($admission->user_id);

        // Create Pending Transaction
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

        $productName = ($feeType === 'admission_fee') ? 'Admission Fee' : 'Application Fee';

        $postData = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePass,
            'total_amount' => $amount,
            'currency' => 'BDT',
            'tran_id' => $transactionId,
            'success_url' => url('/api/payment/success'),
            'fail_url' => url('/api/payment/fail'),
            'cancel_url' => url('/api/payment/cancel'),
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
            'product_name' => $productName,
            'product_category' => 'Education',
            'product_profile' => 'general',
            'value_a' => $transaction->id, // Store transaction PK here to retrieve later
            'value_b' => $feeType,
        ];

        $response = Http::asForm()->post($this->getBaseUrl() . '/gwprocess/v4/api.php', $postData);
        $result = $response->json();

        if (isset($result['status']) && $result['status'] === 'SUCCESS') {
            return response()->json([
                'status' => 'success',
                'gateway_url' => $result['GatewayPageURL']
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to initiate payment gateway.',
            'error' => $result
        ], 500);
    }

    public function paymentSuccess(Request $request)
    {
        $valId = $request->val_id;
        $tranId = $request->tran_id;

        // Validate the transaction with SSLCommerz
        $validationResponse = Http::get($this->getBaseUrl() . '/validator/api/validationserverAPI.php', [
            'val_id' => $valId,
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePass,
            'v' => 1,
            'format' => 'json'
        ]);

        $result = $validationResponse->json();

        if (isset($result['status']) && ($result['status'] === 'VALID' || $result['status'] === 'VALIDATED')) {
            $transaction = PaymentTransaction::where('transaction_id', $tranId)->first();

            if ($transaction) {
                // Determine payment method from gateway response
                $cardType = strtolower($result['card_type'] ?? '');
                $method = 'card';
                if (str_contains($cardType, 'bkash')) $method = 'bkash';
                elseif (str_contains($cardType, 'nagad')) $method = 'nagad';
                elseif (str_contains($cardType, 'rocket')) $method = 'rocket';

                DB::transaction(function () use ($transaction, $result, $method) {
                    $transaction->markPaid($transaction->transaction_id, $result, $method);
                    
                    $admission = Admission::find($transaction->payable_id);
                    if ($admission && $transaction->type === 'application_fee' && $admission->status === 'pending') {
                        $admission->status = 'under_review';
                        $admission->save();
                    }
                });
            }

            return redirect(url('/api/payment/result?status=success'));
        }

        return redirect(url('/api/payment/result?status=fail'));
    }

    public function paymentFail(Request $request)
    {
        $tranId = $request->tran_id;
        $transaction = PaymentTransaction::where('transaction_id', $tranId)->first();
        if ($transaction && $transaction->status !== 'paid') {
            $transaction->status = 'failed';
            $transaction->save();
        }

        return redirect(url('/api/payment/result?status=fail'));
    }

    public function paymentCancel(Request $request)
    {
        $tranId = $request->tran_id;
        $transaction = PaymentTransaction::where('transaction_id', $tranId)->first();
        if ($transaction && $transaction->status !== 'paid') {
            $transaction->status = 'failed';
            $transaction->save();
        }

        return redirect(url('/api/payment/result?status=cancel'));
    }
}
