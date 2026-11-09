<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maestro Crown College - Admission Application Slip</title>
    <style>
        @page {
            margin: 0.4in;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #1A1A1A;
            margin: 0;
            padding: 0;
            background: #FFFFFF;
        }
        .header {
            width: 100%;
            text-align: center;
            border-bottom: 3px solid #C62828;
            padding-bottom: 14px;
            margin-bottom: 14px;
            position: relative;
        }
        .header-content {
            margin: 0 auto;
        }
        .logo {
            width: 140px;
            margin-bottom: 6px;
        }
        .school-info h1 {
            margin: 0;
            color: #C62828;
            font-size: 24px;
            text-transform: uppercase;
            font-weight: 900;
            letter-spacing: 1px;
        }
        .school-info p {
            margin: 3px 0 0 0;
            font-size: 11px;
            color: #555555;
        }
        .qr-section {
            position: absolute;
            left: 0;
            top: 10px;
            width: 130px;
            text-align: left;
        }
        .photo-section {
            position: absolute;
            right: 0;
            top: 10px;
            width: 100px;
            text-align: right;
        }
        .qr-code {
            width: 100px;
            height: 100px;
        }
        .photo {
            width: 85px;
            height: 105px;
            border: 2px solid #D4AF37;
            object-fit: cover;
        }
        
        .title-section {
            text-align: center;
            margin: 16px 0 14px 0;
            padding: 8px;
            background-color: #F8F9FA;
            border: 1px solid #E2E2E2;
            border-radius: 4px;
        }
        .title-section h2 {
            margin: 0;
            font-size: 17px;
            color: #C62828;
            letter-spacing: 1.5px;
        }
        .qr-app-no {
            font-size: 10px;
            font-weight: bold;
            color: #333333;
            margin-top: 4px;
            text-align: center;
            width: 100px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .details-table th, .details-table td {
            border: 1px solid #E2E2E2;
            padding: 7px 10px;
            text-align: left;
            font-size: 11.5px;
        }
        .details-table th {
            background-color: #F8F9FA;
            color: #444444;
            width: 22%;
            font-weight: bold;
        }
        .details-table td {
            width: 28%;
            color: #111111;
        }
        .section-heading {
            background-color: #C62828 !important;
            color: #FFFFFF !important;
            text-align: left;
            font-size: 12px;
            padding: 6px 10px !important;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: bold;
        }

        .portal-notice-box {
            background-color: #FFF8E1;
            border: 1px solid #FFE082;
            border-left: 4px solid #FFA000;
            padding: 8px 12px;
            margin-bottom: 14px;
            border-radius: 4px;
        }
        .portal-notice-title {
            font-weight: bold;
            color: #C62828;
            font-size: 11.5px;
            margin-bottom: 3px;
        }
        .portal-notice-text {
            font-size: 10.5px;
            color: #555555;
            line-height: 1.4;
        }
        
        .footer {
            margin-top: 30px;
            width: 100%;
        }
        .signature-table {
            width: 100%;
            margin-top: 25px;
        }
        .signature-table td {
            width: 33%;
            text-align: center;
            border: none;
            padding: 0;
        }
        .signature-line {
            border-top: 1px solid #1A1A1A;
            padding-top: 6px;
            display: inline-block;
            width: 170px;
            font-weight: bold;
            color: #444444;
            font-size: 12px;
        }
        .watermark {
            position: fixed;
            top: 25%;
            left: 15%;
            opacity: 0.035;
            width: 500px;
            z-index: -1;
        }
        .copy-type {
            font-size: 12px;
            font-weight: bold;
            color: #1A1A1A;
            margin-top: 4px;
            letter-spacing: 1px;
            background: #D4AF37;
            display: inline-block;
            padding: 2px 14px;
            border-radius: 3px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <img src="{{ $logoPath }}" class="watermark" alt="Watermark">

    @php
        $isCollege = $isCollege ?? (!empty($admission->ssc_roll) || stripos((string)$admission->class_name, 'HSC') !== false || stripos((string)$admission->class_name, 'Class XI') !== false);
        $copies = $isCollege ? ['STUDENT COPY', 'OFFICE COPY'] : ['GUARDIAN COPY', 'OFFICE COPY'];
        if ($isCollege) {
            $slipTitle = 'STUDENT ADMISSION APPLICATION SLIP (SESSION 2026)';
        } elseif ($admission->class_name === 'Guardian Portal Registration' || !$admission->user_id) {
            $slipTitle = 'GUARDIAN REGISTRATION SLIP (SESSION 2026)';
        } else {
            $slipTitle = 'GUARDIAN ADMISSION APPLICATION SLIP (SESSION 2026)';
        }
    @endphp

    @foreach($copies as $index => $copyType)
    
    <div class="header">
        <div class="qr-section">
            @if($qrCodeBase64)
                <img src="{{ $qrCodeBase64 }}" class="qr-code" alt="QR Code">
            @endif
            <div class="qr-app-no">{{ $admission->application_no }}</div>
        </div>
        
        <div class="header-content">
            <img src="{{ $logoPath }}" class="logo" alt="Logo">
            <div class="school-info">
                <h1>Maestro Crown College</h1>
                <p>Bangladesh National Curriculum (NCTB) · School & College Section</p>
                <p>107/2, Dogormura, Radio Colony, Dhaka-Aricha Highway, Savar, Dhaka-1343</p>
                <p>Phone: +880 1309-136876 | Email: info@maestrocrown.edu.bd | Web: maestrocrown.edu.bd</p>
            </div>
        </div>
        
        <div class="photo-section">
            @if($photoPath)
                <img src="{{ $photoPath }}" class="photo" alt="Photo">
            @else
                <div class="photo" style="line-height: 105px; color: #999; font-size: 11px; text-align: center;">Student Photo</div>
            @endif
        </div>
    </div>

    <div class="title-section">
        <h2>{{ $slipTitle }}</h2>
        <div class="copy-type">{{ $copyType }}</div>
    </div>

    @if($isCollege)
    <!-- ================= COLLEGE STUDENT ADMISSION SLIP ================= -->
    <!-- 1. Candidate / Student Information -->
    <table class="details-table">
        <tr>
            <th colspan="4" class="section-heading">1. Candidate / Student Information (শিক্ষার্থীর বিবরণ)</th>
        </tr>
        <tr>
            <th>Student Name</th>
            <td colspan="3"><strong style="font-size: 13px; color: #C62828;">{{ $admission->name }}</strong></td>
        </tr>
        <tr>
            <th>Class Applying For</th>
            <td><strong>{{ $admission->class_name ?: 'Class XI (HSC)' }}</strong></td>
            <th>Stream / Group</th>
            <td><strong style="color: #1976D2;">{{ $admission->department ?: 'General' }}</strong></td>
        </tr>
        <tr>
            <th>SSC Board</th>
            <td>{{ $admission->ssc_board ?: 'N/A' }}</td>
            <th>SSC Roll Number</th>
            <td><strong>{{ $admission->ssc_roll ?: 'N/A' }}</strong></td>
        </tr>
        <tr>
            <th>Passing Year</th>
            <td>{{ $admission->passing_year ?: 'N/A' }}</td>
            <th>SSC GPA</th>
            <td><strong style="color: #2E7D32;">{{ $admission->gpa ?: 'N/A' }}</strong></td>
        </tr>
        <tr>
            <th>Student Mobile</th>
            <td><strong>{{ $admission->mobile ?: 'N/A' }}</strong></td>
            <th>Student Email</th>
            <td>{{ $admission->email ?: 'N/A' }}</td>
        </tr>
        <tr>
            <th>Gender</th>
            <td>{{ $admission->gender ?: 'N/A' }}</td>
            <th>Application Status</th>
            <td>
                @if($admission->status === 'approved')
                    <strong style="color: #2E7D32; background: #E8F5E9; padding: 2px 6px; border-radius: 3px; text-transform: uppercase;">APPROVED</strong>
                @elseif($admission->status === 'under_review')
                    <strong style="color: #E65100; background: #FFF3E0; padding: 2px 6px; border-radius: 3px; text-transform: uppercase;">UNDER REVIEW</strong>
                @elseif($admission->status === 'rejected')
                    <strong style="color: #C62828; background: #FFEBEE; padding: 2px 6px; border-radius: 3px; text-transform: uppercase;">REJECTED</strong>
                @else
                    <strong style="color: #E65100; background: #FFF3E0; padding: 2px 6px; border-radius: 3px; text-transform: uppercase;">PENDING</strong>
                @endif
            </td>
        </tr>
        <tr>
            <th>Application Fee</th>
            <td><strong>৳ {{ number_format($paymentAmount ?? 500, 2) }}</strong></td>
            <th>Payment Status</th>
            <td>
                @if(($paymentStatus ?? 'UNPAID') === 'PAID')
                    <strong style="color: #2E7D32; background: #E8F5E9; padding: 2px 6px; border-radius: 3px;">PAID (Verified)</strong>
                    @if(!empty($transactionId))
                        <br><span style="font-size: 9.5px; color: #555;">Txn ID: {{ $transactionId }}</span>
                    @endif
                    @if(!empty($paidAt))
                        <br><span style="font-size: 9px; color: #777;">Paid At: {{ $paidAt }}</span>
                    @endif
                @else
                    <strong style="color: #C62828; background: #FFEBEE; padding: 2px 6px; border-radius: 3px;">PENDING / UNPAID</strong>
                @endif
            </td>
        </tr>
        <tr>
            <th>Father's Name</th>
            <td>{{ $admission->father_name ?: 'N/A' }}</td>
            <th>Mother's Name</th>
            <td>{{ $admission->mother_name ?: 'N/A' }}</td>
        </tr>
        <tr>
            <th>Present Address</th>
            <td colspan="3">{{ $admission->present_address }}</td>
        </tr>
    </table>

    <!-- 2. Guardian Information -->
    <table class="details-table">
        <tr>
            <th colspan="4" class="section-heading">2. Guardian Information (অভিভাবকের তথ্য)</th>
        </tr>
        <tr>
            <th>Guardian Name</th>
            <td><strong>{{ $admission->guardian_name }}</strong></td>
            <th>Relationship</th>
            <td>{{ $admission->relation_with_guardian }}</td>
        </tr>
        <tr>
            <th>Guardian Mobile</th>
            <td><strong>{{ $admission->guardian_mobile }}</strong></td>
            <th>Occupation</th>
            <td>{{ $admission->guardian_occupation ?: 'Service/Business' }}</td>
        </tr>
        <tr>
            <th>National ID (NID)</th>
            <td>{{ $admission->guardian_nid ?: 'N/A' }}</td>
            <th>Emergency Contact</th>
            <td>{{ $admission->guardian_mobile }}</td>
        </tr>
    </table>

    <!-- Student Portal Access Notice Box -->
    <div class="portal-notice-box">
        <div class="portal-notice-title">★ STUDENT PORTAL ACCESS CREDENTIALS ★</div>
        <div class="portal-notice-text">
            <strong>Student ID:</strong> <span style="font-size: 12px; font-weight: bold; color: #C62828;">{{ $studentId ?: ($admission->user?->student_id ?: $admission->student_id) }}</span><br>
            <strong>Login Identifier:</strong> Use your <strong>Student ID</strong> (or registered mobile <strong>{{ $admission->mobile }}</strong>) to sign in to the <strong>Maestro Crown College Mobile App</strong>.<br>
            <strong>Curriculum Portal Access:</strong> Once signed in under the <strong>Student</strong> tab, your portal is automatically tailored to your stream (<strong>{{ $admission->department }} — Class XI HSC</strong>). You can view class routines, subject syllabus, attendance records, notices, and <strong>pay college admission & monthly tuition fees</strong> online.
        </div>
    </div>

    <div class="footer">
        <table class="signature-table">
            <tr>
                <td><div class="signature-line">Student Signature</div></td>
                <td><div class="signature-line">Guardian Signature</div></td>
                <td><div class="signature-line">Principal / Authority</div></td>
            </tr>
        </table>
    </div>

    @else
    <!-- ================= SCHOOL GUARDIAN ADMISSION SLIP ================= -->
    <!-- 1. Guardian Information -->
    <table class="details-table">
        <tr>
            <th colspan="4" class="section-heading">1. Guardian Information (অভিভাবকের তথ্য)</th>
        </tr>
        <tr>
            <th>Guardian Name</th>
            <td><strong>{{ $admission->guardian_name }}</strong></td>
            <th>Relationship</th>
            <td>{{ $admission->relation_with_guardian }}</td>
        </tr>
        <tr>
            <th>Guardian Mobile</th>
            <td><strong>{{ $admission->guardian_mobile }}</strong></td>
            <th>National ID (NID)</th>
            <td><strong>{{ $admission->guardian_nid ?: 'N/A' }}</strong></td>
        </tr>
        <tr>
            <th>Occupation</th>
            <td>{{ $admission->guardian_occupation ?: 'Service/Business' }}</td>
            <th>Email</th>
            <td>{{ $admission->email ?: 'N/A' }}</td>
        </tr>
        <tr>
            <th>Present Address</th>
            <td colspan="3">{{ $admission->present_address }}</td>
        </tr>
    </table>

    @if($admission->class_name === 'Guardian Portal Registration' || !$admission->user_id)
    <!-- 2. Enrolled Children / Student Admissions -->
    <table class="details-table">
        <tr>
            <th colspan="4" class="section-heading">2. Enrolled Children / Student Admissions (সংযুক্ত শিক্ষার্থীর তথ্য)</th>
        </tr>
        <tr>
            <td colspan="4" style="padding: 14px; text-align: center; background-color: #F8F9FA;">
                <strong style="color: #1976D2; font-size: 13px;">Guardian Portal Account Active</strong><br>
                <span style="font-size: 11px; color: #555; display: inline-block; margin-top: 4px;">
                    Log in to the <strong>Maestro Crown College Mobile App</strong> using your Guardian Mobile number to add your children and complete school admission (Play through Class 10).
                </span>
            </td>
        </tr>
    </table>
    @else
    <!-- 2. Student Information -->
    <table class="details-table">
        <tr>
            <th colspan="4" class="section-heading">2. Student Information (শিক্ষার্থীর বিবরণ)</th>
        </tr>
        <tr>
            <th>Student Name</th>
            <td colspan="3"><strong style="font-size: 13px; color: #C62828;">{{ $admission->name }}</strong></td>
        </tr>
        <tr>
            <th>Class Applying For</th>
            <td><strong>{{ $admission->class_name ?: 'Class 1' }}</strong></td>
            <th>Stream / Group</th>
            <td>{{ $admission->department ?: 'General' }}</td>
        </tr>
        <tr>
            <th>Gender</th>
            <td>{{ $admission->gender }}</td>
            <th>Date of Birth</th>
            <td>{{ $admission->date_of_birth ?: 'N/A' }}</td>
        </tr>
        <tr>
            <th>Birth Certificate No</th>
            <td>{{ $admission->birth_certificate_no ?: 'N/A' }}</td>
            <th>Application Status</th>
            <td>
                @if($admission->status === 'approved')
                    <strong style="color: #2E7D32; background: #E8F5E9; padding: 2px 6px; border-radius: 3px; text-transform: uppercase;">APPROVED</strong>
                @elseif($admission->status === 'under_review')
                    <strong style="color: #E65100; background: #FFF3E0; padding: 2px 6px; border-radius: 3px; text-transform: uppercase;">UNDER REVIEW</strong>
                @elseif($admission->status === 'rejected')
                    <strong style="color: #C62828; background: #FFEBEE; padding: 2px 6px; border-radius: 3px; text-transform: uppercase;">REJECTED</strong>
                @else
                    <strong style="color: #E65100; background: #FFF3E0; padding: 2px 6px; border-radius: 3px; text-transform: uppercase;">PENDING</strong>
                @endif
            </td>
        </tr>
        <tr>
            <th>Application Fee</th>
            <td><strong>৳ {{ number_format($paymentAmount ?? 500, 2) }}</strong></td>
            <th>Payment Status</th>
            <td>
                @if(($paymentStatus ?? 'UNPAID') === 'PAID')
                    <strong style="color: #2E7D32; background: #E8F5E9; padding: 2px 6px; border-radius: 3px;">PAID (Verified)</strong>
                    @if(!empty($transactionId))
                        <br><span style="font-size: 9.5px; color: #555;">Txn ID: {{ $transactionId }}</span>
                    @endif
                    @if(!empty($paidAt))
                        <br><span style="font-size: 9px; color: #777;">Paid At: {{ $paidAt }}</span>
                    @endif
                @else
                    <strong style="color: #C62828; background: #FFEBEE; padding: 2px 6px; border-radius: 3px;">PENDING / UNPAID</strong>
                @endif
            </td>
        </tr>
        <tr>
            <th>Father's Name</th>
            <td>{{ $admission->father_name }}</td>
            <th>Mother's Name</th>
            <td>{{ $admission->mother_name }}</td>
        </tr>
    </table>
    @endif

    <!-- Guardian Portal Access Information -->
    <div class="portal-notice-box">
        <div class="portal-notice-title">★ GUARDIAN PORTAL ACCESS CREDENTIALS ★</div>
        <div class="portal-notice-text">
            <strong>Login Identifier:</strong> {{ $admission->guardian_mobile }} (Guardian Mobile Number)<br>
            <strong>Note for Parents:</strong> School students do not have personal phone accounts. Guardians can log in to the <strong>Maestro Crown College Mobile App</strong> using this registered mobile number to monitor academic progress, attendance, exam reports, tuition fee payments, and to add other children.
        </div>
    </div>

    <div class="footer">
        <table class="signature-table">
            <tr>
                <td><div class="signature-line">Guardian Signature</div></td>
                <td>
                    <div style="font-size: 11px; color: #666;">
                        Date: {{ $admission->submitted_at ? $admission->submitted_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}
                    </div>
                </td>
                <td><div class="signature-line">Headmaster / Principal</div></td>
            </tr>
        </table>
    </div>
    @endif

    @if($index == 0)
        <div class="page-break"></div>
    @endif
    
    @endforeach

</body>
</html>
