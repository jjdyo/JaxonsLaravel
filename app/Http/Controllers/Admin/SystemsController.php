<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SystemsSettingsRequest;
use App\Services\Settings\SettingsService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SystemsController extends Controller
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function index(): View|Factory
    {
        $timezones = \DateTimeZone::listIdentifiers();
        $currentTz = $this->settings->getSystemTimezone();
        $siteName = $this->settings->getSiteName();
        $contactEmail = $this->settings->getContactEmail();
        $usersPerPage = $this->settings->getUsersPerPage();

        return view('admin.systems.index', [
            'timezones' => $timezones,
            'currentTimezone' => $currentTz,
            'siteName' => $siteName,
            'contactEmail' => $contactEmail,
            'usersPerPage' => $usersPerPage,
        ]);
    }

    /**
     * Unified save endpoint: only persist settings that actually changed.
     */
    public function update(SystemsSettingsRequest $request): RedirectResponse
    {
        $changed = 0;

        // System Timezone (optional input)
        $incomingTz = $request->input('timezone');
        if (is_string($incomingTz) && $incomingTz !== '') {
            $currentTz = $this->settings->getSystemTimezone();
            if ($incomingTz !== $currentTz) {
                $this->settings->setSystemTimezone($incomingTz);
                $changed++;
            }
        }

        // Site Name
        $incomingSiteName = $request->input('site_name');
        if (is_string($incomingSiteName) && $incomingSiteName !== '') {
            $currentSiteName = $this->settings->getSiteName();
            if ($incomingSiteName !== $currentSiteName) {
                $this->settings->setSiteName($incomingSiteName);
                $changed++;
            }
        }

        // Contact Email
        $incomingContactEmail = $request->input('contact_email');
        if (is_string($incomingContactEmail) && $incomingContactEmail !== '') {
            $currentContactEmail = $this->settings->getContactEmail();
            if ($incomingContactEmail !== $currentContactEmail) {
                $this->settings->setContactEmail($incomingContactEmail);
                $changed++;
            }
        }

        // Users per page (User Management)
        $incomingUsersPerPage = $request->input('users_per_page');
        if ($incomingUsersPerPage !== null && $incomingUsersPerPage !== '' && is_numeric($incomingUsersPerPage)) {
            $incomingUsersPerPage = (int) $incomingUsersPerPage;
            $currentUsersPerPage = $this->settings->getUsersPerPage();
            if ($incomingUsersPerPage !== $currentUsersPerPage) {
                $this->settings->setUsersPerPage($incomingUsersPerPage);
                $changed++;
            }
        }

        return redirect()
            ->route('admin.systems.index')
            ->with('success', $changed > 0 ? 'Settings saved successfully.' : 'No changes to save.');
    }
}
