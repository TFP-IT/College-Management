<?php

namespace App\Modules\Admissions\Http\Controllers\Api\V1;

use App\Modules\Admissions\Actions\InitiatePaymentAction;
use App\Modules\Admissions\Actions\ProcessPaymentCallbackAction;
use App\Modules\Admissions\Enums\PaymentType;
use App\Modules\Admissions\Http\Requests\InitiatePaymentRequest;
use App\Modules\Admissions\Http\Resources\PaymentResource;
use App\Modules\Admissions\Models\AdmissionPayment;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Payments\GatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentApiController extends BaseApiController
{
    public function initiate(string $id, InitiatePaymentRequest $request, InitiatePaymentAction $action): JsonResponse
    {
        $application = Application::where('id', $id)->orWhere('application_no', $id)->firstOrFail();
        $this->authorizeApplication($request, $application);

        return $this->guard(function () use ($action, $application, $request) {
            $result = $action->execute($application, PaymentType::from($request->payment_type), $request->provider);

            return $this->respondWithSuccess([
                'payment' => new PaymentResource($result['payment']),
                'redirect_url' => $result['redirect_url'],
                'session_id' => $result['session_id'],
            ], 'Payment initiated.', [], 201);
        });
    }

    public function initiateFromBody(InitiatePaymentRequest $request, InitiatePaymentAction $action): JsonResponse
    {
        $request->validate(['application_id' => 'required|string']);

        return $this->initiate($request->application_id, $request, $action);
    }

    public function show(Request $request, string $ref): JsonResponse
    {
        $payment = AdmissionPayment::where('transaction_ref', $ref)->firstOrFail();
        $this->authorizeApplication($request, $payment->application);

        return $this->respondWithSuccess(new PaymentResource($payment), 'Payment status.');
    }

    /**
     * Gateway callback / IPN. Accepts form or JSON. Always answers in JSON.
     * Invalid signatures and amount mismatches return 422 and are logged.
     */
    public function callback(Request $request, ProcessPaymentCallbackAction $action): JsonResponse
    {
        $provider = $request->route('provider');
        $ref = (string) ($request->input('transaction_ref') ?? $request->input('tran_id') ?? '');
        $payment = AdmissionPayment::where('transaction_ref', $ref)->first();

        if (!$payment) {
            return $this->respondWithError('Unknown transaction.', null, 404);
        }
        if ($provider && $provider !== $payment->provider) {
            return $this->respondWithError('Provider does not match this transaction.', null, 422);
        }

        return $this->guard(function () use ($action, $payment, $request) {
            $processed = $action->execute($payment, $request->all());

            return $this->respondWithSuccess(new PaymentResource($processed), 'Payment callback processed.');
        });
    }
}
