# Views and Partials

This project uses Blade templates. To keep templates DRY and consistent, common UI fragments are extracted into partials.

## Alerts Partial

A reusable alerts partial is provided to display success and error messages, as well as validation errors.

Location: `resources/views/partials/alerts.blade.php`

Usage in any Blade view:

```
@include('partials.alerts')
```

What it renders automatically:
- `session('success')` as a green success alert
- `session('error')` as a red danger alert
- The `$errors` validation bag as a list

This partial is now used across:
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/show.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `resources/views/pages/profile.blade.php`

If you need to customize styles, update your CSS classes for `.alert`, `.alert-success`, and `.alert-danger` in your stylesheets.
