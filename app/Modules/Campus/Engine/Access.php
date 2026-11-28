<?php

namespace App\Modules\Campus\Engine;

use App\Models\User;

/** Who may manage which module. Confidential groups list their own roles and exclude general admins. */
class Access
{
    /** @return string[] */
    public static function roles(?User $user): array
    {
        if (!$user || (int) $user->status !== 1 || $user->account_status === 'disable') {
            return [];
        }

        $base = [1 => 'admin', 2 => 'admin', 4 => 'accounts', 5 => 'librarian'][(int) $user->role_id] ?? null;

        return array_values(array_filter(array_merge([$base], $user->getRoleNames()->all())));
    }

    public static function manages(?User $user, Module $module): bool
    {
        $allowed = $module->get('manage_roles') ?? $module->group()['roles'];

        return (bool) array_intersect(self::roles($user), $allowed);
    }

    /** @return \Illuminate\Support\Collection<string, Module> */
    public static function managed(?User $user)
    {
        $roles = self::roles($user);

        return $roles ? Module::all()->filter(fn (Module $m) => (bool) array_intersect($roles, $m->get('manage_roles') ?? $m->group()['roles'])) : collect();
    }

    /** An approval step is a role. "office" means whoever manages the module. */
    public static function canApproveStep(User $user, Module $module, string $step): bool
    {
        if ($step === 'office') {
            return self::manages($user, $module);
        }

        $roles = self::roles($user);
        if (in_array($step, $roles, true)) {
            return true;
        }

        // The principal's approval is personal. Other steps can be covered by an administrator.
        return $step !== 'Principal' && in_array('admin', $roles, true) && empty($module->group()['confidential']);
    }
}
