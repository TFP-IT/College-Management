<?php

namespace App\Console\Commands;

use App\Models\Admission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RemovePublicAdmissionSlips extends Command
{
    protected $signature = 'admissions:remove-public-slips';

    protected $description = 'Delete admission slips that older code saved in the public folder (they are rebuilt on demand)';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $count = count($disk->files('admissions/pdfs'));
        $disk->deleteDirectory('admissions/pdfs');

        Admission::withTrashed()->where('pdf_path', 'like', 'admissions/pdfs/%')->update(['pdf_path' => null]);

        $this->info("Removed {$count} public admission slip(s).");

        return self::SUCCESS;
    }
}
