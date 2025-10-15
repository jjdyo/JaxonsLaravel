<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * @method mixed input(string $key = null, mixed $default = null)
 * @method Authenticatable|null user(string|null $guard = null)
 */
class SystemsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasAnyRole(['admin', 'moderator']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // All settings are optional; only provided inputs are considered for update
            'timezone' => ['nullable', 'string', function (string $attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return; // optional
                }
                if (!is_string($value) || !in_array($value, \DateTimeZone::listIdentifiers(), true)) {
                    $fail('Invalid timezone.');
                }
            }],
            'site_name' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'users_per_page' => ['nullable', 'integer', 'min:5', 'max:200'],
        ];
    }
}
