<?php

namespace App\Modules\Admissions\Services;

use App\Modules\Admissions\Enums\DocStatus;
use App\Modules\Admissions\Enums\DocType;
use App\Modules\Admissions\Models\Application;
use App\Modules\Admissions\Models\ApplicationDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Stores applicant documents on the private disk. The database keeps metadata and the object key only.
 * The MIME type is taken from the file's content (finfo), not from the client's claim.
 */
class DocumentService
{
    /** extension => allowed content MIME types */
    protected const ALLOWED = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
    ];

    public function __construct(private AuditService $audit) {}

    public function disk(): string
    {
        return config('admissions.document_disk', 'local');
    }

    public function store(Application $application, UploadedFile $file, DocType $docType, $uploader = null): ApplicationDocument
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $sniffed = $this->sniffMime($file->getRealPath());

        if (!isset(self::ALLOWED[$ext]) || !in_array($sniffed, self::ALLOWED[$ext], true)) {
            throw new InvalidArgumentException('Only PDF, JPG, PNG or WEBP files are accepted.');
        }

        $limit = $docType === DocType::PHOTO
            ? config('admissions.max_photo_bytes')
            : config('admissions.max_document_bytes');

        if ($file->getSize() > $limit) {
            throw new InvalidArgumentException($docType === DocType::PHOTO ? 'Photo must be 1 MB or smaller.' : 'File must be 5 MB or smaller.');
        }

        $count = $application->documents()->count();
        $sameType = $application->documents()->where('doc_type', $docType->value)->first();
        if (!$sameType && $count >= config('admissions.max_documents_per_application')) {
            throw new InvalidArgumentException('An application can have at most ' . config('admissions.max_documents_per_application') . ' documents.');
        }

        $objectKey = "admissions/{$application->id}/" . Str::uuid() . '.' . $ext;
        $checksum = hash_file('sha256', $file->getRealPath());
        Storage::disk($this->disk())->putFileAs(dirname($objectKey), $file, basename($objectKey));

        // Replacing a document of the same type removes the old object.
        if ($sameType) {
            Storage::disk($this->disk())->delete($sameType->object_key);
            $sameType->delete();
        }

        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'doc_type' => $docType->value,
            'object_key' => $objectKey,
            'file_name' => Str::limit($file->getClientOriginalName(), 240, ''),
            'mime_type' => $sniffed,
            'size_bytes' => $file->getSize(),
            'checksum_sha256' => $checksum,
            'status' => DocStatus::UPLOADED->value,
        ]);

        $this->audit->record('document_uploaded', $document->id, [], ['doc_type' => $docType->value, 'application_id' => $application->id], $uploader);

        return $document;
    }

    public function sniffMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return (string) $finfo->file($path);
    }

    public function getSecureUrl(ApplicationDocument $document, int $expirationMinutes = 10): string
    {
        return URL::temporarySignedRoute(
            'admissions.documents.download',
            now()->addMinutes($expirationMinutes),
            ['document' => $document->id]
        );
    }
}
