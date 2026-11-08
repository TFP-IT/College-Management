<?php

namespace App\Services;

use App\Models\Admission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use PDF;

class AdmissionPdfService
{
    /**
     * Generate PDF for admission application.
     *
     * @param Admission $admission
     * @return string Returns the storage path of the generated PDF.
     */
    public function generate(Admission $admission): string
    {
        try {
            // Generate QR Code encoding application number (SVG format does not require Imagick extension)
            $qrCodeSvg = QrCode::format('svg')->size(200)->margin(1)->generate($admission->application_no);
            $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);

            $logoPath = public_path('assets/logo.png');

            $photoPath = null;
            if ($admission->photo_path) {
                // Ensure absolute path from storage
                $photoPath = storage_path('app/public/' . $admission->photo_path);
            }

            $isCollege = !empty($admission->ssc_roll) 
                || stripos((string)$admission->class_name, 'HSC') !== false 
                || stripos((string)$admission->class_name, 'Class XI') !== false;

            $studentId = null;
            if ($admission->user_id) {
                $studentUser = \App\Models\User::find($admission->user_id);
                $studentId = $studentUser?->student_id;
            }
            if (!$studentId) {
                $studentId = $admission->student_id;
            }

            // Render PDF View
            $pdf = PDF::loadView('pdf.admission-slip', [
                'admission' => $admission,
                'logoPath' => $logoPath,
                'photoPath' => $photoPath,
                'qrCodeBase64' => $qrCodeBase64,
                'isCollege' => $isCollege,
                'studentId' => $studentId,
            ]);

            // Save PDF to public disk
            $pdfPath = 'admissions/pdfs/' . $admission->application_no . '.pdf';
            
            Storage::disk('public')->put($pdfPath, $pdf->output());

            // Update database with pdf_path
            $admission->update(['pdf_path' => $pdfPath]);

            return $pdfPath;
        } catch (\Throwable $e) {
            Log::error('Admission PDF Generation failed: ' . $e->getMessage(), [
                'application_no' => $admission->application_no ?? 'unknown',
                'exception' => $e
            ]);
            
            return '';
        }
    }
}
