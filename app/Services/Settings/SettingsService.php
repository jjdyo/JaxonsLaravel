<?php

namespace App\Services\Settings;

use App\Repositories\SettingsRepository;
use Illuminate\Support\Facades\Config;

class SettingsService
{
    public const KEY_SYSTEM_TIMEZONE = 'system.timezone';
    public const KEY_SYSTEM_SITE_NAME = 'system.site_name';
    public const KEY_SYSTEM_CONTACT_EMAIL = 'system.contact_email';
    public const KEY_USERS_PAGINATION = 'users.pagination';

    public function __construct(private readonly SettingsRepository $repo)
    {
    }

    public function getSystemTimezone(): string
    {
        $raw = $this->repo->get(self::KEY_SYSTEM_TIMEZONE, '');
        $tz = is_string($raw) ? $raw : '';
        if ($tz === '') {
            // Fallback to current config
            $cfg = Config::get('app.timezone', 'UTC');
            return is_string($cfg) ? $cfg : 'UTC';
        }
        return $tz;
    }

    public function setSystemTimezone(string $timezone): void
    {
        $this->repo->set(self::KEY_SYSTEM_TIMEZONE, $timezone);
        // Apply at runtime
        Config::set('app.timezone', $timezone);
        @date_default_timezone_set($timezone);
    }

    public function getSiteName(): string
    {
        $raw = $this->repo->get(self::KEY_SYSTEM_SITE_NAME, '');
        $name = is_string($raw) ? $raw : '';
        if ($name === '') {
            $cfg = Config::get('app.name', 'Laravel');
            return is_string($cfg) ? $cfg : 'Laravel';
        }
        return $name;
    }

    public function setSiteName(string $name): void
    {
        $this->repo->set(self::KEY_SYSTEM_SITE_NAME, $name);
        // Optionally also set runtime app.name used in some places
        Config::set('app.name', $name);
    }

    public function getContactEmail(): string
    {
        $raw = $this->repo->get(self::KEY_SYSTEM_CONTACT_EMAIL, '');
        $email = is_string($raw) ? $raw : '';
        if ($email === '') {
            $cfg = Config::get('mail.from.address', '');
            return is_string($cfg) ? $cfg : '';
        }
        return $email;
    }

    public function setContactEmail(string $email): void
    {
        $this->repo->set(self::KEY_SYSTEM_CONTACT_EMAIL, $email);
        // do not override mail.from.address automatically to avoid side-effects in runtime mail config
    }

    public function getUsersPerPage(): int
    {
        $value = $this->repo->get(self::KEY_USERS_PAGINATION, null);
        $perPage = is_numeric($value) ? (int) $value : 15;
        // clamp to sane bounds
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 200) { $perPage = 200; }
        return $perPage;
    }

    public function setUsersPerPage(int $perPage): void
    {
        // clamp
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 200) { $perPage = 200; }
        $this->repo->set(self::KEY_USERS_PAGINATION, $perPage);
    }
}
