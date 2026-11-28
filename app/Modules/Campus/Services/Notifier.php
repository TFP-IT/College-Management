<?php

namespace App\Modules\Campus\Services;

use App\Models\User;
use App\Modules\Campus\Models\CampusNotification;
use Illuminate\Support\Facades\DB;

/**
 * The one place notifications are created. Push and SMS channels attach here later,
 * so no feature needs to know how a message is delivered.
 * Bodies stay free of sensitive detail because they can show on a locked screen.
 */
class Notifier
{
    /** @param iterable<int> $userIds */
    public function send(iterable $userIds, string $category, string $title, ?string $body = null, array $data = []): int
    {
        $now = now();
        $rows = collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique()->map(fn ($id) => [
            'user_id' => $id,
            'category' => $category,
            'title' => mb_strimwidth($title, 0, 160, '…'),
            'body' => $body === null ? null : mb_strimwidth($body, 0, 500, '…'),
            'data' => json_encode($data),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $rows->chunk(500)->each(fn ($chunk) => CampusNotification::insert($chunk->values()->all()));

        return $rows->count();
    }

    /**
     * A student and the linked guardian. Guardians receive the child's id so the app opens the right profile.
     *
     * @param iterable<int> $studentIds
     */
    public function toFamilies(iterable $studentIds, string $category, string $title, ?string $body = null, array $data = []): int
    {
        $students = User::whereIn('id', collect($studentIds))->get(['id', 'name', 'parent_id']);
        $count = 0;

        foreach ($students as $student) {
            $payload = $data + ['student_id' => $student->id];
            $count += $this->send([$student->id], $category, $title, $body, $payload);
            if ($student->parent_id) {
                $first = explode(' ', trim($student->name))[0];
                $count += $this->send([$student->parent_id], $category, "{$first}: {$title}", $body, $payload);
            }
        }

        return $count;
    }

    /** @return \Illuminate\Support\Collection<int, int> student ids enrolled in a class (optionally one section) */
    public function studentsIn(int $classId, ?int $sectionId = null)
    {
        return DB::table('enrollments')
            ->join('users', 'users.id', '=', 'enrollments.user_id')
            ->where('users.role_id', 7)
            ->where('users.status', 1)
            ->where('enrollments.class_id', $classId)
            ->when($sectionId, fn ($q) => $q->where('enrollments.section_id', $sectionId))
            ->pluck('users.id')
            ->unique()
            ->values();
    }
}
