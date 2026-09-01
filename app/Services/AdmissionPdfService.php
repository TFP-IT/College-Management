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
            // Generate QR Code encoding application number
            $qrCodeImage = QrCode::format('png')->size(200)->margin(1)->generate($admission->application_no);
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage);

            $logoPath = public_path('assets/logo.png');

            $photoPath = null;
            if ($admission->photo_path) {
                // Ensure absolute path from storage
                $photoPath = storage_path('app/public/' . $admission->photo_path);
            }

            // Render PDF View
            $pdf = PDF::loadView('pdf.admission-slip', [
                'admission' => $admission,
                'logoPath' => $logoPath,
                'photoPath' => $photoPath,
                'qrCodeBase64' => $qrCodeBase64
            ]);

            // Save PDF to public disk
            $pdfPath = 'admissions/pdfs/' . $admission->application_no . '.pdf';
            
            Storage::disk('public')->put($pdfPath, $pdf->output());

            // Update database with pdf_path
            $admission->update(['pdf_path' => $pdfPath]);

            return $pdfPath;
        } catch (\Exception $e) {
            Log::error('Admission PDF Generation failed: ' . $e->getMessage(), [
                'application_no' => $admission->application_no ?? 'unknown',
                'exception' => $e
            ]);
            
            return '';
        }
    }
}
