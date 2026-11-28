<?php

namespace App\Modules\Campus\Models;

use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    protected $table = 'fee_payments';

    protected $guarded = ['id'];

    protected $casts = ['paid_at' => 'datetime', 'amount' => 'integer'];
}
