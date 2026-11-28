<?php

namespace App\Modules\Campus\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $table = 'teacher_assignments';

    protected $guarded = ['id'];

    protected $casts = ['due_at' => 'datetime', 'allow_late' => 'boolean'];

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'assignment_id');
    }
}
