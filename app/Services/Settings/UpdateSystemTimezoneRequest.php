<?php

namespace App\Services\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Form Request for updating the System Timezone setting.
 *
 * Note: This class was moved from App\\Http\\Requests to App\\Services\\Settings
 * to group settings-related requests alongside the Settings domain code.
 *
 * @method mixed input(string $key = null, mixed $default = null)
 * @method Authenticatable|null user(string|null $guard = null)
 */
class UpdateSystemTimezoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced via route middleware; double-check here for safety
        return $this->user() !== null && $this->user()->hasAnyRole(['admin','moderator']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'timezone' => ['required', 'string', function (string $attribute, $value, $fail) {
                if (!is_string($value)) {
                    $fail('Invalid timezone.');
                    return;
                }
                if (!in_array($value, \DateTimeZone::listIdentifiers(), true)) {
                    $fail('Invalid timezone.');
                }
            }],
        ];
    }
}
