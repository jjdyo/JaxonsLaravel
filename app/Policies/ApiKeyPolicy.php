<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Laravel\Sanctum\PersonalAccessToken;

class ApiKeyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any API keys.
     *
     * @param  User  $user
     * @param  User  $targetUser
     * @return bool
     */
    public function viewApiKeys(User $user, User $targetUser): bool
    {
        // Admin can view any user's API keys
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can view their own API keys
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can create API keys.
     *
     * @param  User  $user
     * @param  User  $targetUser
     * @return bool
     */
    public function createApiKey(User $user, User $targetUser): bool
    {
        // Admin can create API keys for any user
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can create their own API keys
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can delete the API key.
     *
     * @param  User  $user
     * @param  User  $targetUser
     * @param PersonalAccessToken $token
     * @return bool
     */
    public function deleteApiKey(User $user, User $targetUser, PersonalAccessToken $token): bool
    {
        // Admin can delete any user's API keys
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can delete their own API keys
        return $user->id === $targetUser->id && $targetUser->id === $token->tokenable_id;
    }
}
