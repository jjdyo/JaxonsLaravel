<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Services\Authorization\RoleHierarchy;
use App\Services\Authorization\PermissionHierarchy;
use App\Services\Settings\SettingsService;

class UserManagementController extends Controller
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    /**
     * Display a listing of all users
     *
     * @return View The users index view with all users
     */
    public function listUsers(Request $request): View
    {
        $filterParam = $request->get('filter', 'new');
        $filter = is_string($filterParam) ? $filterParam : 'new';

        $qParam = $request->get('q', '');
        $q = is_string($qParam) ? $qParam : '';

        $query = User::query()
            ->selectSummary()
            ->withRolesMinimal();

        switch ($filter) {
            case 'unverified':
                $query->unverified()->newest();
                break;
            case 'az':
                $query->orderByName('asc');
                break;
            case 'za':
                $query->orderByName('desc');
                break;
            case 'search':
                if (trim($q) !== '') {
                    $query->search($q)->orderByName('asc');
                } else {
                    $query->newest();
                }
                break;
            case 'new':
            default:
                $query->newest();
                break;
        }

        $perPage = $this->settings->getUsersPerPage();
        $users = $query->paginate($perPage)->withQueryString();

        /** @var View $view */
        $view = view('admin.users.index', [
            'users' => $users,
            'filter' => $filter,
            'q' => $q,
        ]);
        return $view;
    }

    /**
     * Display the specified user
     *
     * @param \App\Models\User $user The user to display
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory The user detail view
     */
    public function showUser(User $user): View|Factory
    {
        // Ensure roles and permissions are loaded to avoid N+1 in the view
        $user->loadMissing(['roles:id,name', 'permissions:id,name']);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     *
     * @param User $user The user to edit
     * @return View The user edit form view
     */
    public function editUser(User $user): View
    {
        $user->loadMissing(['roles:id,name', 'permissions:id,name']);

        $actor = auth()->user();
        // Deny access to edit form if actor cannot manage the target (except allow self)
        if (!($actor instanceof User)) {
            abort(403);
        }
        if ($actor->id !== $user->id && !RoleHierarchy::canManageUser($actor, $user)) {
            // Mirror other controller methods: redirect back with error message
            abort(403, 'You do not have permission to edit this user.');
        }

        $allRoles = Role::query()->select(['id', 'name'])->orderBy('name')->get();
        // At this point, $actor is guaranteed to be an authenticated User (checked above)
        $assignableNames = RoleHierarchy::assignableRoleNames($actor, $allRoles->all());
        $roles = $allRoles->whereIn('name', $assignableNames)->values();

        $allPermissions = Permission::query()->select(['id', 'name'])->orderBy('name')->get();
        // Filter permissions to only those the actor is allowed to manage by level
        $allowedPermissionNames = PermissionHierarchy::filterAllowed(
            $actor,
            array_values(array_filter($allPermissions->pluck('name')->all(), static fn($v): bool => is_string($v)))
        );
        $permissions = $allPermissions->whereIn('name', $allowedPermissionNames)->values();
        /** @var View $view */
        $view = view('admin.users.edit', compact('user', 'roles', 'permissions'));

        return $view;
    }

    /**
     * Update the specified user in storage
     *
     * @param Request $request The HTTP request with user data
     * @param User $user The user to update
     * @return RedirectResponse Redirect to the user's detail page
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'email_verified' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $actor = auth()->user();
        if (!($actor instanceof User) || !RoleHierarchy::canManageUser($actor, $user)) {
            return back()->withErrors(['error' => 'You do not have permission to update this user.']);
        }

        $forceSync = filter_var($request->get('permissions_force_sync', false), FILTER_VALIDATE_BOOLEAN);

        try {
            DB::transaction(function () use ($actor, $user, $validated, $forceSync) {
                $user->name = $validated['name'];
                $user->email = $validated['email'];

                if (!empty($validated['password'])) {
                    $user->password = $validated['password']; // cast('password' => 'hashed') handles hashing
                }

                // Toggle email verification if provided (delegated to model helpers)
                if (array_key_exists('email_verified', $validated)) {
                    if ($validated['email_verified']) {
                        $user->markEmailVerified();
                    } else {
                        $user->markEmailUnverified();
                    }
                }

                $user->save();

                // Sync roles from the validated data, filtered by actor's assignable roles
                $roleIds = $validated['roles'] ?? [];
                $roleNamesRaw = empty($roleIds)
                    ? []
                    : Role::query()->whereIn('id', $roleIds)->pluck('name')->all();
                // Ensure a strictly typed array<int,string> for static analysis
                $roleNames = array_values(array_filter($roleNamesRaw, static fn($v): bool => is_string($v)));
                /** @var array<int,string> $roleNames */
                $roleNames = RoleHierarchy::filterAssignable($actor, $roleNames);
                $user->syncRoles(...$roleNames);

                // Sync direct permissions from the validated data while preserving any existing
                // permissions that are above the actor's permission level (so they cannot be removed).
                if (array_key_exists('permissions', $validated) || $forceSync) {
                    $permissionIds = $validated['permissions'] ?? [];

                    // Resolve requested permission names from IDs
                    $requestedNames = empty($permissionIds)
                        ? []
                        : Permission::query()->whereIn('id', $permissionIds)->pluck('name')->all();
                    // Ensure strictly typed string array
                    $requestedNames = array_values(array_filter($requestedNames, static fn($v): bool => is_string($v)));

                    // Keep only permissions the actor is allowed to manage.
                    // For top-level admins (max role level), allow all requested permissions even if not mapped.
                    $actorLevel = RoleHierarchy::highestLevelForUser($actor);
                    /** @var array<string,int> $hier */
                    $hier = (array) config('roles.hierarchy', []);
                    $levels = array_values($hier);
                    $maxLevel = empty($levels) ? 0 : max($levels);
                    $allowedRequested = ($actorLevel === $maxLevel)
                        ? $requestedNames
                        : PermissionHierarchy::filterAllowed($actor, $requestedNames);

                    // Determine existing direct permissions on the user
                    $existingNames = $user->permissions()->pluck('name')->all();
                    $existingNames = array_values(array_filter($existingNames, static fn($v): bool => is_string($v)));

                    // Of the existing, which are allowed vs protected relative to the actor?
                    $existingAllowed = PermissionHierarchy::filterAllowed($actor, $existingNames);
                    $existingProtected = array_values(array_diff($existingNames, $existingAllowed));

                    // Final set: allowed requested + protected existing (prevents removal of higher-level perms)
                    $finalNames = array_values(array_unique(array_merge($allowedRequested, $existingProtected)));

                    $user->syncPermissions($finalNames);
                }
            });

            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', 'User updated successfully');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to update user.'])->withInput();
        }
    }

    /**
     * Remove the specified user from storage
     *
     * @param User $user The user to delete
     * @return RedirectResponse Redirect to the users index
     */
    public function deleteUser(User $user): RedirectResponse
    {
        $actor = auth()->user();
        // Prevent deletion when actor is the same user (centralized in model) and enforce role hierarchy
        if (!$user->canBeDeletedBy($actor)) {
            return back()->withErrors(['error' => "You can't delete your own account."]);
        }
        if (!($actor instanceof User) || !RoleHierarchy::canManageUser($actor, $user)) {
            return back()->withErrors(['error' => 'You do not have permission to delete this user.']);
        }

        try {
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', 'User deleted successfully');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to delete user.']);
        }
    }

    /**
     * Manually verify a user's email
     *
     * @param User $user The user to verify
     * @return RedirectResponse Redirect to the user's detail page
     */
    public function verifyUser(User $user): RedirectResponse
    {
        $actor = auth()->user();
        if (!($actor instanceof User) || !RoleHierarchy::canManageUser($actor, $user)) {
            return back()->withErrors(['error' => 'You do not have permission to verify this user.']);
        }

        $user->markEmailVerified();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User email verified successfully');
    }

    /**
     * Manually unverify a user's email
     *
     * @param User $user The user to unverify
     * @return RedirectResponse Redirect to the user's detail page
     */
    public function unverifyUser(User $user): RedirectResponse
    {
        $actor = auth()->user();
        if (!($actor instanceof User) || !RoleHierarchy::canManageUser($actor, $user)) {
            return back()->withErrors(['error' => 'You do not have permission to unverify this user.']);
        }

        $user->markEmailUnverified();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User email verification removed');
    }

    /**
     * Update the roles assigned to a user
     *
     * @param Request $request The HTTP request with role data
     * @param User $user The user to update roles for
     * @return RedirectResponse Redirect to the user's detail page
     */
    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
        ]);

        try {
            $roleIds = $validated['roles'] ?? [];
            // Sync using IDs to avoid extra queries; Spatie supports arrays of IDs
            $user->syncRoles(Role::query()->whereIn('id', $roleIds)->pluck('name'));

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'User roles updated successfully');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to update user roles.']);
        }
    }

    /**
     * Update the direct permissions assigned to a user
     */
    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $actor = auth()->user();
        if (!($actor instanceof User)) {
            abort(403);
        }
        if ($actor->id !== $user->id && !RoleHierarchy::canManageUser($actor, $user)) {
            return back()->withErrors(['error' => 'You do not have permission to update this user.']);
        }

        try {
            $permissionIds = $validated['permissions'] ?? [];
            $requestedNames = empty($permissionIds)
                ? []
                : Permission::query()->whereIn('id', $permissionIds)->pluck('name')->all();
            // Ensure strictly typed string arrays for static analysis
            $requestedNames = array_values(array_filter($requestedNames, static fn($v): bool => is_string($v)));

            // Only allow assigning permissions at or below the actor's level
            $allowedRequested = PermissionHierarchy::filterAllowed($actor, $requestedNames);

            // Preserve existing permissions that the actor is NOT allowed to manage (so they cannot remove them)
            $existingNames = $user->permissions()->pluck('name')->all();
            $existingNames = array_values(array_filter($existingNames, static fn($v): bool => is_string($v)));
            $existingAllowed = PermissionHierarchy::filterAllowed($actor, $existingNames);
            $existingProtected = array_values(array_diff($existingNames, $existingAllowed));

            // Final set to sync: allowed requested + protected existing
            $finalNames = array_values(array_unique(array_merge($allowedRequested, $existingProtected)));
            $user->syncPermissions($finalNames);

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'User permissions updated successfully');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['error' => 'Failed to update user permissions.']);
        }
    }
}
