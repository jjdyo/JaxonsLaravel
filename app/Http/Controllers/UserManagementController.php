<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    private const USERS_PER_PAGINATION = 15;
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

        $users = $query->paginate(self::USERS_PER_PAGINATION)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filter' => $filter,
            'q' => $q,
        ]);
    }

    /**
     * Display the specified user
     *
     * @param \App\Models\User $user The user to display
     * @return \Illuminate\View\View The user detail view
     */
    public function showUser(User $user): View
    {
        // Ensure roles are loaded to avoid N+1 in the view
        $user->loadMissing(['roles:id,name']);
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
        $user->loadMissing(['roles:id,name']);
        $roles = Role::query()->select(['id', 'name'])->orderBy('name')->get();
        /** @var \Illuminate\View\View $view */
        // @phpstan-ignore-next-line
        $view = view('admin.users.edit', compact('user', 'roles'));

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
        ]);

        try {
            DB::transaction(function () use ($user, $validated) {
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
        // Prevent deletion when actor is the same user (centralized in model)
        if (!$user->canBeDeletedBy(auth()->user())) {
            return back()->withErrors(['error' => "You can't delete your own account."]);
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
}
