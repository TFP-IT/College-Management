<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $table = 'payment_transactions';

    protected $fillable = [
        'payable_type',
        'payable_id',
        'user_id',
        'type',
        'period',
        'amount',
        'currency',
        'method',
        'transaction_id',
        'application_no',
        'student_id',
        'student_name',
        'class_name',
        'status',
        'due_date',
        'paid_at',
        'gateway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'gateway_response' => 'array',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markPaid(?string $transactionId = null, array $gatewayResponse = [], ?string $method = null): void
    {
        $this->status = 'paid';
        $this->paid_at = now();
        
        if ($transactionId !== null) {
            $this->transaction_id = $transactionId;
        }
        
        if (!empty($gatewayResponse)) {
            $this->gateway_response = $gatewayResponse;
        }

        if ($method !== null) {
            $this->method = $method;
        }
        
        $this->save();
    }
}
