<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
</head>
<body style="background-color: #374151; color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #374151; padding: 20px; min-height: 100vh;">
    <tr>
        <td align="center">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; margin: 0 auto; background-color: #4B5563; border-radius: 12px; padding: 40px;">
                <tr>
                    <td align="center">
                        <!-- Logo -->
                        <div style="margin-bottom: 30px;">
                            <img src="{{ asset('images/your-logo.png') }}" alt="{{ config('app.name') }}" style="height: 60px; max-width: 200px;">
                        </div>

                        <!-- Header -->
                        <h1 style="color: #ffffff; font-size: 28px; font-weight: 600; margin-bottom: 20px; text-align: center;">Password Reset Request</h1>

                        <!-- Greeting -->
                        <div style="color: #E5E7EB; font-size: 18px; margin-bottom: 25px;">
                            Hello {{ $notifiable->name ?? 'User' }},
                        </div>

                        <!-- Main message -->
                        <div style="color: #D1D5DB; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
                            We received a request to reset your password for your {{ config('app.name') }} account.
                        </div>

                        <!-- Reset button -->
                        <div style="margin: 20px 0;">
                            <a href="{{ $url }}" style="display: inline-block; background-color: #3B82F6; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">Reset Your Password</a>
                        </div>

                        <!-- Security information -->
                        <div style="background-color: #1F2937; border-radius: 8px; padding: 20px; margin: 30px 0; text-align: left;">
                            <h3 style="color: #F59E0B; font-size: 16px; margin-bottom: 15px; text-align: center;">Important Security Information</h3>
                            <ul style="color: #D1D5DB; font-size: 14px; line-height: 1.6; margin: 0; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">This password reset link will expire in {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes</li>
                                <li style="margin-bottom: 8px;">If you didn't request this password reset, please ignore this email</li>
                                <li style="margin-bottom: 8px;">Your current password remains unchanged until you create a new one</li>
                            </ul>
                        </div>

                        <!-- Recommendations -->
                        <div style="background-color: #1F2937; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: left;">
                            <h4 style="color: #10B981; font-size: 16px; margin-bottom: 15px; text-align: center;">For your security, we recommend:</h4>
                            <ul style="color: #D1D5DB; font-size: 14px; line-height: 1.6; margin: 0; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">Using a strong, unique password</li>
                                <li style="margin-bottom: 8px;">Enabling two-factor authentication if available</li>
                                <li style="margin-bottom: 8px;">Never sharing your password with anyone</li>
                            </ul>
                        </div>

                        <!-- Fallback URL -->
                        <div style="background-color: #1F2937; border-radius: 6px; padding: 15px; margin: 20px 0; word-break: break-all; font-size: 14px;">
                            <div style="color: #9CA3AF; font-size: 14px; margin-bottom: 10px;">
                                If you're having trouble clicking the "Reset Your Password" button, copy and paste the URL below into your web browser:
                            </div>
                            <a href="{{ $url }}" style="color: #60A5FA; text-decoration: none;">{{ $url }}</a>
                        </div>

                        <!-- Signature -->
                        <div style="margin-top: 40px; color: #D1D5DB; font-size: 16px;">
                            Thanks,<br>
                            The {{ config('app.name') }} Team
                        </div>

                        <!-- Footer -->
                        <div style="margin-top: 30px; color: #9CA3AF; font-size: 12px; text-align: center;">
                            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
