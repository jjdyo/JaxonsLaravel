# Admin Systems Page

The Admin Systems page provides configuration options for the application. It is restricted to users with the admin or moderator roles and requires verified email.

Path: /admin/systems
Route names: 
- admin.systems.index (GET)
- admin.systems.update (POST)

Workflow
- The page groups settings (e.g., "System Configuration").
- There is a single "Save settings" button at the bottom that submits all settings.
- Only settings that actually changed are persisted; unchanged values are ignored.

Current sections
- System Configuration
  - System Timezone: choose from a list of valid PHP timezones. Saving updates the application timezone at runtime and persists to the settings table (key: system.timezone).

Notes
- The timezone applies immediately without requiring a deploy. On subsequent requests, the timezone is applied during application boot if the settings table exists.
- Access is protected by middleware: auth, verified, and role:admin|moderator.
- Legacy per-setting endpoint (/admin/systems/timezone) is temporarily routed to the unified save for compatibility, but the UI uses the single-save flow.
