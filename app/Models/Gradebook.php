<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gradebook extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'class_id', 'section_id', 'student_id', 'exam_category_id', 'marks', 'comment', 'school_id', 'session_id', 'timestamp'
    ];

    /**
     * Every screen that writes marks passes through here: published marks are locked until the office
     * sends them back, and a teacher cannot change marks that are waiting for approval.
     */
    protected static function booted(): void
    {
        static::saving(function (Gradebook $book) {
            $status = \App\Modules\Campus\Models\ResultPublication::where([
                'session_id' => $book->session_id,
                'exam_category_id' => $book->exam_category_id,
                'class_id' => $book->class_id,
                'section_id' => $book->section_id,
            ])->value('status');

            abort_if($status === 'published', 423, 'These results are published. Send them back from the office portal before changing marks.');
            abort_if($status === 'submitted' && (int) auth()->user()?->role_id === 3, 423, 'These results are with the office and can no longer be changed.');
        });
    }
}
