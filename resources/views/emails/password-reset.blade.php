@component('mail::message')
    {{-- Custom CSS for dark theme --}}
    <style>
        body {
            background-color: #374151 !important;
            color: #ffffff !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .email-wrapper {
            background-color: #374151 !important;
            padding: 20px;
            min-height: 100vh;
        }

        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #4B5563;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .logo-container {
            margin-bottom: 30px;
        }

        .logo-container img {
            height: 60px;
            max-width: 200px;
        }

        h1 {
            color: #ffffff !important;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }

        .greeting {
            color: #E5E7EB !important;
            font-size: 18px;
            margin-bottom: 25px;
        }

        .main-text {
            color: #D1D5DB !important;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .reset-button {
            display: inline-block;
            background-color: #3B82F6 !important;
            color: #ffffff !important;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: background-color 0.3s ease;
        }

        .reset-button:hover {
            background-color: #2563EB !important;
        }

        .security-info {
            background-color: #1F2937;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }

        .security-info h3 {
            color: #F59E0B !important;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
        }

        .security-info ul {
            color: #D1D5DB !important;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            padding-left: 20px;
        }

        .security-info li {
            margin-bottom: 8px;
        }

        .recommendations {
            background-color: #1F2937;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .recommendations h4 {
            color: #10B981 !important;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
        }

        .fallback-url {
            background-color: #1F2937;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
            font-size: 14px;
        }

        .fallback-text {
            color: #9CA3AF !important;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .fallback-url a {
            color: #60A5FA !important;
            text-decoration: none;
        }

        .signature {
            margin-top: 40px;
            color: #D1D5DB !important;
            font-size: 16px;
        }

        .footer {
            margin-top: 30px;
            color: #9CA3AF !important;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 600px) {
            .email-content {
                padding: 20px;
                margin: 10px;
            }

            h1 {
                font-size: 24px;
            }

            .reset-button {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>

    <div class="email-wrapper">
        <div class="email-content">
            {{-- Logo --}}
            <div class="logo-container">
                <img src="{{ asset('images/your-logo.png') }}" alt="{{ config('app.name') }}">
            </div>

            {{-- Header --}}
            <h1>Password Reset Request</h1>

            {{-- Greeting --}}
            <div class="greeting">
                Hello {{ $notifiable->name ?? 'User' }},
            </div>

            {{-- Main message --}}
            <div class="main-text">
                We received a request to reset your password for your {{ config('app.name') }} account.
            </div>

            {{-- Reset button --}}
            <a href="{{ $url }}" class="reset-button">Reset Your Password</a>

            {{-- Security information --}}
            <div class="security-info">
                <h3>Important Security Information</h3>
                <ul>
                    <li>This password reset link will expire in {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes</li>
                    <li>If you didn't request this password reset, please ignore this email</li>
                    <li>Your current password remains unchanged until you create a new one</li>
                </ul>
            </div>

            {{-- Recommendations --}}
            <div class="recommendations">
                <h4>For your security, we recommend:</h4>
                <ul>
                    <li>Using a strong, unique password</li>
                    <li>Enabling two-factor authentication if available</li>
                    <li>Never sharing your password with anyone</li>
                </ul>
            </div>

            {{-- Fallback URL --}}
            <div class="fallback-url">
                <div class="fallback-text">
                    If you're having trouble clicking the "Reset Your Password" button, copy and paste the URL below into your web browser:
                </div>
                <a href="{{ $url }}">{{ $url }}</a>
            </div>

            {{-- Signature --}}
            <div class="signature">
                Thanks,<br>
                The {{ config('app.name') }} Team
            </div>

            {{-- Footer --}}
            <div class="footer">
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
        </div>
    </div>
@endcomponent
