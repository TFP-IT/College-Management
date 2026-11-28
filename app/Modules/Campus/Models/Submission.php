<?php

namespace App\Modules\Campus\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $table = 'assignment_submissions';

    protected $guarded = ['id'];

    protected $casts = ['submitted_at' => 'datetime', 'graded_at' => 'datetime', 'is_late' => 'boolean', 'marks' => 'float'];

    public function student()
    {
        return $this->belongsTo(\App\Models\User::class, 'student_id');
    }
}
