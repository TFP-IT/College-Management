<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * Safety net for every upload. Many older screens move a file into the public folder under the name
 * the browser sent, so anything that is not a known document, image or media type is refused here.
 * Controllers should still validate the narrower set they actually expect.
 */
class RejectDangerousUploads
{
    private const ALLOWED = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico', 'heic',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'rtf', 'csv', 'txt',
        'mp3', 'mp4', 'mov', 'webm',
    ];

    private const SCRIPT_SEGMENT = '/\.(php[0-9]?|phtml|phar|pht|phps|cgi|shtml)(\.|$)/i';

    private const SCRIPT_TYPES = [
        'text/x-php', 'application/x-php', 'application/x-httpd-php', 'text/html', 'application/xhtml+xml',
        'image/svg+xml', 'text/x-shellscript',
    ];

    public function handle(Request $request, Closure $next)
    {
        foreach (Arr::dot($request->allFiles()) as $field => $file) {
            if ($file instanceof UploadedFile && !$this->isSafe($file)) {
                throw ValidationException::withMessages([
                    $field => ['This type of file cannot be uploaded.'],
                ]);
            }
        }

        return $next($request);
    }

    private function isSafe(UploadedFile $file): bool
    {
        $name = $file->getClientOriginalName();
        $allowed = self::ALLOWED;
        if (config('security.updater_enabled')) {
            $allowed[] = 'zip';
        }

        if (str_starts_with($name, '.')
            || preg_match(self::SCRIPT_SEGMENT, $name)
            || !in_array(strtolower($file->getClientOriginalExtension()), $allowed, true)) {
            return false;
        }

        return !$file->isValid() || !in_array($file->getMimeType(), self::SCRIPT_TYPES, true);
    }
}
