<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Carbon;

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
        $apiKeys = ApiKey::getAllForUser($user);

        /** @var View $view */
                $view = view('admin.users.api-keys.index', compact('user', 'apiKeys'));
                return $view;
    }

    /**
     * Show the form for creating a new API key (admin view).
     *
     * @param User $user The user to create an API key for
     * @return View The API key creation form
     */
    public function create(User $user): View|Factory
    {
        /** @var View $view */
                $view = view('admin.users.api-keys.create', compact('user'));
                return $view;
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
            'name' => 'required|string|alpha_num|max:255',
            'abilities' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ], [
            'name.alpha_num' => 'The token name may only contain letters and numbers.',
        ]);

        $abilities = $validated['abilities'] ?? ['*'];
        $expiresAt = isset($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null;

        $token = ApiKey::createForUser(
            $user,
            $validated['name'],
            $abilities,
            $expiresAt
        );

        return redirect()->route('admin.users.api-keys.index', $user)
            ->with('success', 'API key created successfully')
            ->with('plainTextApiKey', $token->plainTextToken);
    }

    /**
     * Remove the specified API key from storage (admin view).
     *
     * @param User $user The user who owns the API key
     * @param ApiKey $token The token to delete
     * @return RedirectResponse Redirect to the API keys index
     */
    public function destroy(User $user, ApiKey $token): RedirectResponse
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
        /** @var User|null $user */
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $apiKeys = ApiKey::getAllForUser($user);

        /** @var View $view */
        $view = view('user.api-tokens.index', compact('apiKeys'));
        return $view;
    }

    /**
     * Show the form for creating a new API key for the authenticated user.
     *
     * @return View The API key creation form
     */
    public function userCreate(): View
    {
        /** @var View $view */
        $view = view('user.api-tokens.create');
        return $view;
    }

    /**
     * Store a newly created API key for the authenticated user.
     *
     * @param Request $request The HTTP request
     * @return RedirectResponse Redirect to the API keys index
     */
    public function userStore(Request $request): RedirectResponse
    {
        $scopes = config('api-scopes.scopes', []);
        $availableScopes = is_array($scopes) ? array_keys($scopes) : [];

        $validated = $request->validate([
            'name' => 'required|string|alpha_num|max:100',
            'expiration' => 'required|in:week,month,year',
            'scopes' => 'required|array|min:1',
            'scopes.*' => 'required|string|in:' . implode(',', $availableScopes),
        ], [
            'name.alpha_num' => 'The token name may only contain letters and numbers.',
            'scopes.required' => 'At least one scope must be selected.',
            'scopes.min' => 'At least one scope must be selected.',
            'scopes.*.in' => 'One or more selected scopes are invalid.',
        ]);

        /** @var User|null $user */
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $token = ApiKey::createWithExpiration(
            $user,
            $validated['name'],
            $validated['scopes'],
            $validated['expiration']
        );

        return redirect()->route('api-tokens.index')
            ->with('success', 'API token created successfully')
            ->with('plainTextApiKey', $token->plainTextToken);
    }

    /**
     * Display the specified API token.
     *
     * @param ApiKey $token The token to show
     * @return View The token details view
     */
    public function userShow(ApiKey $token): View
    {
        $this->authorizeToken($token);

        /** @var View $view */
        $view = view('user.api-tokens.show', compact('token'));
        return $view;
    }

    /**
     * Remove the specified API token from storage.
     *
     * @param ApiKey $token The token to delete
     * @return RedirectResponse Redirect to the API tokens index
     */
    public function userDestroy(ApiKey $token): RedirectResponse
    {
        $this->authorizeToken($token);

        $token->delete();

        return redirect()->route('api-tokens.index')
            ->with('success', 'API token revoked successfully');
    }

    /**
     * Authorize that the token belongs to the authenticated user.
     *
     * @param ApiKey $token The token to authorize
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function authorizeToken(ApiKey $token): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (!$user || !$token->belongsToUser($user)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
