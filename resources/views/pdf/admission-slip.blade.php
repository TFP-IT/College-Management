<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admission Application Slip</title>
    <style>
        @page {
            margin: 0.5in;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #1A1A1A;
            margin: 0;
            padding: 0;
            background: #FFFFFF;
        }
        .header {
            width: 100%;
            text-align: center;
            border-bottom: 3px solid #C62828;
            padding-bottom: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        .header-content {
            margin: 0 auto;
        }
        .logo {
            width: 160px;
            margin-bottom: 10px;
        }
        .college-info h1 {
            margin: 0;
            color: #C62828;
            font-size: 26px;
            text-transform: uppercase;
            font-weight: 900;
        }
        .college-info p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #666666;
        }
        .qr-section {
            position: absolute;
            left: 0;
            top: 20px;
            width: 150px;
            text-align: left;
        }
        .photo-section {
            position: absolute;
            right: 0;
            top: 20px;
            width: 100px;
            text-align: right;
        }
        .qr-code {
            width: 110px;
            height: 110px;
        }
        .photo {
            width: 90px;
            height: 110px;
            border: 2px solid #D4AF37;
            object-fit: cover;
        }
        
        .title-section {
            text-align: center;
            margin: 30px 0;
            padding: 10px;
            background-color: #F5F7FA;
            border: 1px solid #E2E2E2;
        }
        .title-section h2 {
            margin: 0;
            font-size: 20px;
            color: #C62828;
            letter-spacing: 2px;
        }
        .qr-app-no {
            font-size: 11px;
            font-weight: bold;
            color: #4A4A4A;
            margin-top: 5px;
            text-align: center;
            width: 110px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .details-table th, .details-table td {
            border: 1px solid #E2E2E2;
            padding: 10px 12px;
            text-align: left;
        }
        .details-table th {
            background-color: #F5F7FA;
            color: #666666;
            width: 20%;
            font-weight: bold;
        }
        .details-table td {
            width: 30%;
        }
        .status {
            text-transform: uppercase;
            font-weight: bold;
            color: #C62828;
        }
        .section-heading {
            background-color: #D4AF37 !important;
            color: #FFFFFF !important;
            text-align: center;
            font-size: 14px;
            padding: 8px !important;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .footer {
            margin-top: 60px;
            width: 100%;
        }
        .signature-table {
            width: 100%;
            margin-top: 50px;
        }
        .signature-table td {
            width: 33%;
            text-align: center;
            border: none;
            padding: 0;
        }
        .signature-line {
            border-top: 1px solid #1A1A1A;
            padding-top: 8px;
            display: inline-block;
            width: 180px;
            font-weight: bold;
            color: #666666;
            font-size: 14px;
        }
        .watermark {
            position: fixed;
            top: 25%;
            left: 15%;
            opacity: 0.04;
            width: 500px;
            z-index: -1;
        }
        .copy-type {
            font-size: 14px;
            font-weight: bold;
            color: #1A1A1A;
            margin-top: 5px;
            letter-spacing: 1px;
            background: #D4AF37;
            display: inline-block;
            padding: 3px 15px;
            border-radius: 4px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <img src="{{ $logoPath }}" class="watermark" alt="Watermark">

    @foreach(['STUDENT COPY', 'OFFICE COPY'] as $index => $copyType)
    
    <div class="header">
        <div class="qr-section">
            @if($qrCodeBase64)
                <img src="{{ $qrCodeBase64 }}" class="qr-code" alt="QR Code">
            @endif
            <div class="qr-app-no">{{ $admission->application_no }}</div>
        </div>
        
        <div class="header-content">
            <img src="{{ $logoPath }}" class="logo" alt="Logo">
            <div class="college-info">
                <h1>Maestro Crown College</h1>
                <p>107/2, Dogormura, Radio Colony, Dhaka Aricha Highway, Savar, Bangladesh, 1343</p>
                <p>Phone: +880 1309-136876 | Email: admin@maestrocrown.edu.bd</p>
            </div>
        </div>
        
        <div class="photo-section">
            @if($photoPath)
                <img src="{{ $photoPath }}" class="photo" alt="Photo">
            @else
                <div class="photo" style="line-height: 110px; color: #999; font-size: 12px; text-align: center;">Photo</div>
            @endif
        </div>
    </div>

    <div class="title-section">
        <h2>ADMISSION APPLICATION (2026-2027)</h2>
        <div class="copy-type">{{ $copyType }}</div>
    </div>

    <table class="details-table">
        <tr>
            <th colspan="4" class="section-heading">Applicant Information</th>
        </tr>
        <tr>
            <th>Name</th>
            <td colspan="3">{{ $admission->name }}</td>
        </tr>
        <tr>
            <th>Gender</th>
            <td>{{ $admission->gender }}</td>
            <th>Mobile</th>
            <td>{{ $admission->mobile }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td colspan="3">{{ $admission->email }}</td>
        </tr>
        
        <tr>
            <th colspan="4" class="section-heading">Academic Details</th>
        </tr>
        <tr>
            <th>Department</th>
            <td>{{ $admission->department }}</td>
            <th>Passing Year</th>
            <td>{{ $admission->passing_year }}</td>
        </tr>
        <tr>
            <th>SSC Board</th>
            <td>{{ $admission->ssc_board }}</td>
            <th>GPA</th>
            <td>{{ $admission->gpa }}</td>
        </tr>
        <tr>
            <th>SSC Roll</th>
            <td>{{ $admission->ssc_roll }}</td>
            <th>Reg No</th>
            <td>{{ $admission->registration_number }}</td>
        </tr>
        
        <tr>
            <th colspan="4" class="section-heading">Guardian Information</th>
        </tr>
        <tr>
            <th>Father's Name</th>
            <td colspan="3">{{ $admission->father_name }}</td>
        </tr>
        <tr>
            <th>Mother's Name</th>
            <td colspan="3">{{ $admission->mother_name }}</td>
        </tr>
        <tr>
            <th>Guardian</th>
            <td>{{ $admission->guardian_name }} ({{ $admission->relation_with_guardian }})</td>
            <th>Mobile</th>
            <td>{{ $admission->guardian_mobile }}</td>
        </tr>
        <tr>
            <th>Present Address</th>
            <td colspan="3">{{ $admission->present_address }}</td>
        </tr>
    </table>

    <div class="footer">
        <table class="signature-table">
            <tr>
                <td><div class="signature-line">Student Signature</div></td>
                <td>
                    <div style="font-size: 12px; color: #666;">
                        Date: {{ $admission->submitted_at ? $admission->submitted_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}
                    </div>
                </td>
                <td><div class="signature-line">Authorized Signature</div></td>
            </tr>
        </table>
    </div>

    @if($index == 0)
        <div class="page-break"></div>
    @endif
    
    @endforeach

</body>
</html>
