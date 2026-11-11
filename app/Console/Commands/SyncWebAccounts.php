<?php

namespace App\Console\Commands;

use App\Services\WebAccountSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncWebAccounts extends Command
{
    protected $signature = 'mcc:sync-web-accounts
                            {--setup : Also create the school, running session and lifetime subscription the web admin needs, if missing}
                            {--school=1 : School id used by the app accounts}';

    protected $description = 'Make teacher, guardian and student accounts created in the app usable in the web admin.';

    public function handle(WebAccountSync $sync): int
    {
        if ($this->option('setup')) {
            $this->setupSchool((int) $this->option('school'));
        }

        $result = $sync->syncAll();

        $this->info("Accounts checked: {$result['users']} | profiles completed: {$result['profiles']} | students enrolled: {$result['enrolled']}");
        foreach ($result['skipped'] as $id => $reason) {
            $this->warn("  Not enrolled — user {$id} {$reason}");
        }

        return self::SUCCESS;
    }

    private function setupSchool(int $schoolId): void
    {
        $now = now();

        if (!DB::table('schools')->where('id', $schoolId)->exists()) {
            DB::table('schools')->insert($this->withRequiredBlanks('schools', [
                'id' => $schoolId,
                'title' => 'Maestro Crown College',
                'email' => 'info@maestrocrown.edu.bd',
                'address' => 'Savar, Dhaka',
                'status' => 1,
                'school_currency' => '৳',
                'currency_position' => 'left',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            $this->line("Created school {$schoolId} (Maestro Crown College).");
        }

        $sessionId = DB::table('schools')->where('id', $schoolId)->value('running_session');
        if (!$sessionId) {
            $sessionId = DB::table('sessions')->where('school_id', $schoolId)->where('status', 1)->value('id');
            if (!$sessionId) {
                $year = (int) $now->format('Y');
                $sessionId = DB::table('sessions')->insertGetId([
                    'session_title' => "{$year}-" . ($year + 1),
                    'status' => 1,
                    'school_id' => $schoolId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->line("Created session {$year}-" . ($year + 1) . '.');
            }
            DB::table('schools')->where('id', $schoolId)->update(['running_session' => $sessionId]);
        }

        if (!DB::table('subscriptions')->where('school_id', $schoolId)->where('status', 1)->exists()) {
            $packageId = DB::table('packages')->where('package_type', 'life_time')->value('id')
                ?? DB::table('packages')->insertGetId([
                    'name' => 'MCC Lifetime',
                    'price' => '0',
                    'package_type' => 'life_time',
                    'interval' => 'life_time',
                    'days' => 0,
                    'studentLimit' => '100000',
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            DB::table('subscriptions')->insert([
                'package_id' => $packageId,
                'school_id' => $schoolId,
                'paid_amount' => 0,
                'payment_method' => 'offline',
                'transaction_keys' => '[]',
                'expire_date' => 0,
                'studentLimit' => '100000',
                'date_added' => $now->timestamp,
                'active' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->line('Activated a lifetime subscription for the web admin.');
        }
    }

    /**
     * Older databases declare some optional columns as required; give those a blank of the right type.
     */
    private function withRequiredBlanks(string $table, array $values): array
    {
        $columns = DB::select(
            'select column_name name, data_type type from information_schema.columns
             where table_schema = database() and table_name = ? and is_nullable = ? and column_default is null and extra not like ?',
            [$table, 'NO', '%auto_increment%']
        );

        foreach ($columns as $column) {
            if (!array_key_exists($column->name, $values)) {
                $values[$column->name] = in_array($column->type, ['int', 'bigint', 'smallint', 'tinyint', 'mediumint', 'decimal', 'double', 'float'], true) ? 0 : '';
            }
        }

        return $values;
    }
}
