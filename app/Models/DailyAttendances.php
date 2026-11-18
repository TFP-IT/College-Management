<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DailyAttendances extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'class_id', 'section_id', 'student_id', 'status', 'note', 'source', 'session_id', 'school_id', 'timestamp'
    ];

    /** Manual marks from the website or the teacher app. Biometric punches can use source later. */
    public static function manualAttributes(int $status, ?string $note): array
    {
        $status = in_array($status, [0, 1, 2], true) ? $status : 0;
        $attrs = ['status' => $status];

        if (Schema::hasColumn('daily_attendances', 'note')) {
            $reason = trim((string) $note);
            $attrs['note'] = in_array($status, [0, 2], true) && $reason !== '' ? $reason : null;
        }
        if (Schema::hasColumn('daily_attendances', 'source')) {
            $attrs['source'] = 'manual';
        }

        return $attrs;
    }
}
