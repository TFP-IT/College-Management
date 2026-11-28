<?php

namespace App\Modules\Campus\Support;

use App\Models\User;
use App\Modules\Campus\Engine\Access;

/** Which parts of the office portal an account may open. */
class OfficeAccess
{
    private const ALL = ['dashboard', 'students', 'attendance', 'fees', 'results', 'announcements', 'help', 'insights', 'library', 'elections', 'audit', 'modules', 'approvals', 'messages'];

    /** @return string[] */
    public static function areas(?User $user): array
    {
        $roles = Access::roles($user);
        $areas = [];

        if (in_array('admin', $roles, true)) {
            return self::ALL;
        }
        if (in_array('Principal', $roles, true)) {
            // The principal approves and oversees; money is recorded by the accounts office.
            $areas = ['dashboard', 'students', 'attendance', 'results', 'announcements', 'help', 'insights', 'elections', 'audit'];
        }
        if (in_array('accounts', $roles, true)) {
            $areas = array_merge($areas, ['dashboard', 'students', 'fees']);
        }
        if (in_array('librarian', $roles, true)) {
            $areas[] = 'library';
        }
        if (Access::managed($user)->isNotEmpty()) {
            $areas = array_merge($areas, ['modules', 'approvals', 'messages']);
        }

        return array_values(array_unique($areas));
    }

    public static function allows(?User $user, string $area): bool
    {
        return in_array($area, self::areas($user), true);
    }
}
