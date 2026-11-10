<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Account Setup</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1E293B;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F8FAFC; padding: 40px 10px;">
        <tr>
            <td align="center">
                <table width="100%" max-width="520" cellpadding="0" cellspacing="0" style="max-width: 520px; background-color: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #C62828; padding: 24px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #FFFFFF; font-size: 20px; font-weight: 800; letter-spacing: 0.5px;">
                                MAESTRO CROWN COLLEGE
                            </h1>
                            <p style="margin: 4px 0 0; color: rgba(255,255,255,0.85); font-size: 13px;">
                                Faculty & Teacher Portal
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 32px 30px;">
                            <p style="margin: 0 0 16px; font-size: 16px; font-weight: 700; color: #0F172A;">
                                Dear {{ $teacherName }},
                            </p>
                            <p style="margin: 0 0 20px; font-size: 14px; line-height: 22px; color: #475569;">
                                You have been onboarded as a faculty member at Maestro Crown College. To set up your password and access the teacher mobile app, please use the 6-digit verification code below:
                            </p>
                            
                            <!-- OTP Box -->
                            <div style="background-color: #FEF2F2; border: 1.5px dashed #F87171; border-radius: 12px; padding: 18px; text-align: center; margin: 24px 0;">
                                <div style="font-size: 12px; font-weight: 700; color: #991B1B; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
                                    Your Verification Code
                                </div>
                                <div style="font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #C62828; font-family: monospace;">
                                    {{ $code }}
                                </div>
                            </div>

                            <p style="margin: 0 0 8px; font-size: 13px; color: #64748B; line-height: 20px;">
                                ⏱️ This code will expire in <strong>10 minutes</strong>.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #94A3B8; line-height: 18px;">
                                If you did not request this account setup code, please contact the college administration immediately.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F1F5F9; padding: 16px 30px; text-align: center; border-top: 1px solid #E2E8F0;">
                            <p style="margin: 0; font-size: 11px; color: #64748B;">
                                © {{ date('Y') }} Maestro Crown College. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

