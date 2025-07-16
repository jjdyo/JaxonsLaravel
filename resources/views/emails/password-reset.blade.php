@component('mail::message')
    {{-- Header --}}
    @component('mail::header', ['url' => config('app.url')])
        <img src="{{ asset('images/your-logo.png') }}" alt="{{ config('app.name') }}" style="height: 50px;">
    @endcomponent

    # Password Reset Request

    Hello {{ $notifiable->name ?? 'User' }},

    We received a request to reset your password for your {{ config('app.name') }} account.

    @component('mail::button', ['url' => $url, 'color' => 'primary'])
        Reset Your Password
    @endcomponent

    **Important Security Information:**
    - This password reset link will expire in {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes
    - If you didn't request this password reset, please ignore this email
    - Your current password remains unchanged until you create a new one

    For your security, we recommend:
    - Using a strong, unique password
    - Enabling two-factor authentication if available
    - Never sharing your password with anyone

    If you're having trouble clicking the "Reset Your Password" button, copy and paste the URL below into your web browser:
    {{ $url }}

    Thanks,<br>
    The {{ config('app.name') }} Team

    @component('mail::footer')
        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    @endcomponent
@endcomponent
