<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * Display a listing of all users
     *
     * @return \Illuminate\View\View The users index view with all users
     */
    public function listUsers(): View
    {
        $users = User::paginate(15);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Display the specified user
     *
     * @param \App\Models\User $user The user to display
     * @return \Illuminate\View\View The user detail view
     */
    public function showUser(User $user): View
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     *
     * @param \App\Models\User $user The user to edit
     * @return \Illuminate\View\View The user edit form view
     */
    public function editUser(User $user): View
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage
     *
     * @param \Illuminate\Http\Request $request The HTTP request with user data
     * @param \App\Models\User $user The user to update
     * @return \Illuminate\Http\RedirectResponse Redirect to the user's detail page
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        return redirect()->route('admin.users.update', $user);
    }

    /**
     * Remove the specified user from storage
     *
     * @param \App\Models\User $user The user to delete
     * @return \Illuminate\Http\RedirectResponse Redirect to the users index
     */
    public function deleteUser(User $user): RedirectResponse
    {
        return redirect()->route('admin.users.destroy', $user);
    }

    /**
     * Manually verify a user's email
     *
     * @param \App\Models\User $user The user to verify
     * @return \Illuminate\Http\RedirectResponse Redirect to the user's detail page
     */
    public function verifyUser(User $user): RedirectResponse
    {
        if (!$user->hasVerifiedEmail()) {
            $user->email_verified_at = now();
            $user->save();
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User email verified successfully');
    }

    /**
     * Manually unverify a user's email
     *
     * @param \App\Models\User $user The user to unverify
     * @return \Illuminate\Http\RedirectResponse Redirect to the user's detail page
     */
    public function unverifyUser(User $user): RedirectResponse
    {
        $user->email_verified_at = null;
        $user->save();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User email verification removed');
    }

    /**
     * Update the roles assigned to a user
     *
     * @param \Illuminate\Http\Request $request The HTTP request with role data
     * @param \App\Models\User $user The user to update roles for
     * @return \Illuminate\Http\RedirectResponse Redirect to the user's detail page
     */
    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        return route('admin.users.update', $user);
    }
}
