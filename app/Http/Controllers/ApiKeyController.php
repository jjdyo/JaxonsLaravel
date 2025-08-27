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
     * Display a listing of the user's API keys (admin view).
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
     * Show the form for creating a new API key (admin view).
     *
     * @param User $user The user to create an API key for
     * @return View The API key creation form
     */
    public function create(User $user): View
    {
        return view('admin.users.api-keys.create', compact('user'));
    }

    /**
     * Store a newly created API key in storage (admin view).
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
     * Remove the specified API key from storage (admin view).
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

    /**
     * Display a listing of the authenticated user's API keys.
     *
     * @return View The API keys index view
     */
    public function userIndex(): View
    {
        $user = auth()->user();
        $apiKeys = $user->tokens()->orderBy('created_at', 'desc')->get();

        return view('user.api-tokens.index', compact('apiKeys'));
    }

    /**
     * Show the form for creating a new API key for the authenticated user.
     *
     * @return View The API key creation form
     */
    public function userCreate(): View
    {
        return view('user.api-tokens.create');
    }

    /**
     * Store a newly created API key for the authenticated user.
     *
     * @param Request $request The HTTP request
     * @return RedirectResponse Redirect to the API keys index
     */
    public function userStore(Request $request): RedirectResponse
    {
        $availableScopes = array_keys(config('api-scopes.scopes', []));

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'expiration' => 'required|in:week,month,year',
            'scopes' => 'required|array|min:1',
            'scopes.*' => 'required|string|in:' . implode(',', $availableScopes),
        ], [
            'scopes.required' => 'At least one scope must be selected.',
            'scopes.min' => 'At least one scope must be selected.',
            'scopes.*.in' => 'One or more selected scopes are invalid.',
        ]);

        $user = auth()->user();
        $expiresAt = null;

        // Set expiration date based on selection
        switch ($validated['expiration']) {
            case 'week':
                $expiresAt = now()->addWeek();
                break;
            case 'month':
                $expiresAt = now()->addMonth();
                break;
            case 'year':
                $expiresAt = now()->addYear();
                break;
        }

        $token = $user->createToken(
            $validated['name'],
            $validated['scopes'], // Use selected scopes
            $expiresAt
        );

        return redirect()->route('api-tokens.index')
            ->with('success', 'API token created successfully')
            ->with('plainTextApiKey', $token->plainTextToken);
    }

    /**
     * Display the specified API token.
     *
     * @param PersonalAccessToken $token The token to show
     * @return View The token details view
     */
    public function userShow(PersonalAccessToken $token): View
    {
        $this->authorizeToken($token);

        return view('user.api-tokens.show', compact('token'));
    }

    /**
     * Remove the specified API token from storage.
     *
     * @param PersonalAccessToken $token The token to delete
     * @return RedirectResponse Redirect to the API tokens index
     */
    public function userDestroy(PersonalAccessToken $token): RedirectResponse
    {
        $this->authorizeToken($token);

        $token->delete();

        return redirect()->route('api-tokens.index')
            ->with('success', 'API token revoked successfully');
    }

    /**
     * Authorize that the token belongs to the authenticated user.
     *
     * @param PersonalAccessToken $token The token to authorize
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function authorizeToken(PersonalAccessToken $token): void
    {
        if ($token->tokenable_id !== auth()->id() || $token->tokenable_type !== User::class) {
            abort(403, 'Unauthorized action.');
        }
    }
}
