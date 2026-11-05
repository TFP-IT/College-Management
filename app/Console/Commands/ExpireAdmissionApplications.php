<?php

namespace App\Console\Commands;

use App\Modules\Admissions\Actions\ExpireApplicationsAction;
use Illuminate\Console\Command;

class ExpireAdmissionApplications extends Command
{
    protected $signature = 'admissions:expire-applications';

    protected $description = 'Expire admission applications whose programme deadline has passed.';

    public function handle(ExpireApplicationsAction $action): int
    {
        $count = $action->execute();
        $this->info("Expired {$count} application(s).");

        return self::SUCCESS;
    }
}
