<?php

namespace App\Modules\Admissions\Services;

use App\Models\User;
use App\Modules\Admissions\Models\AuditLog;

/**
 * Writes one audit_logs row per sensitive action. Personal data is never logged here,
 * only identifiers and status values.
 */
class AuditService
{
    public function record(
        string $action,
        string $recordId,
        array $old = [],
        array $new = [],
        ?User $user = null,
        string $module = 'admissions'
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'old_value' => $old ?: null,
            'new_value' => $new ?: null,
            'ip_address' => $request?->ip(),
            'device' => substr((string) $request?->userAgent(), 0, 255) ?: null,
            'created_at' => now(),
        ]);
    }
}
