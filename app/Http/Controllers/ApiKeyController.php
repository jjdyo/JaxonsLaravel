<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class ApiKeyController extends Controller
{
    /**
     * Display a listing of the user's API keys.
     *
     * @param User $user The user whose API keys to display
     * @return View The API keys index view
     */
    public function index(User $user): View
    {
        $apiKeys = $user->tokens()->orderBy('created_at', 'desc')->get();

        return view('admin.users.api-keys.index', compact('user', 'apiKeys'));
    }

    /**
     * Show the form for creating a new API key.
     *
     * @param User $user The user to create an API key for
     * @return View The API key creation form
     */
    public function create(User $user): View
    {
        return view('admin.users.api-keys.create', compact('user'));
    }

    /**
     * Store a newly created API key in storage.
     *
     * @param Request $request The HTTP request
     * @param User $user The user to create an API key for
     * @return RedirectResponse Redirect to the API keys index
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $abilities = $validated['abilities'] ?? ['*'];

        $token = $user->createToken(
            $validated['name'],
            $abilities,
            $validated['expires_at'] ?? null
        );

        return redirect()->route('admin.users.api-keys.index', $user)
            ->with('success', 'API key created successfully')
            ->with('plainTextApiKey', $token->plainTextToken);
    }

    /**
     * Remove the specified API key from storage.
     *
     * @param User $user The user who owns the API key
     * @param PersonalAccessToken $token The token to delete
     * @return RedirectResponse Redirect to the API keys index
     */
    public function destroy(User $user, PersonalAccessToken $token): RedirectResponse
    {
        $token->delete();

        return redirect()->route('admin.users.api-keys.index', $user)
            ->with('success', 'API key revoked successfully');
    }
}
