<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Password Change</title>
</head>
<body style="background-color: #374151; color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #374151; padding: 20px; min-height: 100vh;">
    <tr>
        <td align="center">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; margin: 0 auto; background-color: #4B5563; border-radius: 12px; padding: 40px;">
                <tr>
                    <td align="center">
                        <div style="margin-bottom: 30px;">
                            <img src="https://raw.githubusercontent.com/jjdyo/JaxonsLaravel/refs/heads/main/public/media/jdwob.png" style="height: 200px; max-width: 200px;">
                        </div>

                        <h1 style="color: #ffffff; font-size: 28px; font-weight: 600; margin-bottom: 20px; text-align: center;">Confirm Your Password Change</h1>

                        <div style="color: #E5E7EB; font-size: 18px; margin-bottom: 25px;">
                            Hello {{ $notifiable->name ?? 'User' }},
                        </div>

                        <div style="color: #D1D5DB; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
                            We received a request to change the password for your {{ config('app.name') }} account. For your security, your password will not be changed until you confirm this action.
                        </div>

                        <div style="margin: 20px 0;">
                            <a href="{{ $url }}" style="display: inline-block; background-color: #3B82F6; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">Confirm Password Change</a>
                        </div>

                        <div style="background-color: #1F2937; border-radius: 8px; padding: 20px; margin: 30px 0; text-align: left;">
                            <h3 style="color: #F59E0B; font-size: 16px; margin-bottom: 15px; text-align: center;">Important Information</h3>
                            <ul style="color: #D1D5DB; font-size: 14px; line-height: 1.6; margin: 0; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">This confirmation link will expire in 20 minutes</li>
                                <li style="margin-bottom: 8px;">If you did not request this change, you can safely ignore this email and your password will remain unchanged</li>
                                <li style="margin-bottom: 8px;">Never share this link with anyone</li>
                            </ul>
                        </div>

                        <div style="background-color: #1F2937; border-radius: 6px; padding: 15px; margin: 20px 0; word-break: break-all; font-size: 14px;">
                            <div style="color: #9CA3AF; font-size: 14px; margin-bottom: 10px;">
                                If you're having trouble clicking the "Confirm Password Change" button, copy and paste the URL below into your web browser:
                            </div>
                            <a href="{{ $url }}" style="color: #60A5FA; text-decoration: none;">{{ $url }}</a>
                        </div>

                        <div style="margin-top: 40px; color: #D1D5DB; font-size: 16px;">
                            Thanks,<br>
                            The {{ config('app.name') }} Team
                        </div>

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
