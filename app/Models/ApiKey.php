<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\NewAccessToken;

/**
 * @mixin IdeHelperApiKey
 */
class ApiKey extends PersonalAccessToken
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'personal_access_tokens';
    /**
     * Get all API keys for a user, ordered by creation date.
     *
     * @param  User  $user
     * @return Collection<int, ApiKey>
     */
    public static function getAllForUser(User $user): Collection
    {
        /** @var Collection<int, ApiKey> $result */
        $result = static::query()
            ->forUser($user)
            ->orderByDesc('created_at')
            ->get();

        return $result;
    }

    /**
     * Create a new API key for the given user.
     *
     * @param  User    $user
     * @param  string  $name
     * @param  array<string>  $abilities  Abilities (Sanctum abilities), e.g. ['*'] or ['read', 'write']
     * @param  \DateTimeInterface|null  $expiresAt  When the token expires
     * @return NewAccessToken
     */
    public static function createForUser(User $user, string $name, array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null)
    {
        return $user->createToken($name, $abilities, $expiresAt);
    }

    /**
     * Create a new API key for the given user with an expiration window.
     *
     * @param  User    $user
     * @param  string  $name
     * @param  array<string>  $scopes      List of scopes/abilities for the token
     * @param  string  $expiration         'week'|'month'|'year'
     * @return NewAccessToken
     */
    public static function createWithExpiration(User $user, string $name, array $scopes, string $expiration)
    {
        $expiresAt = match ($expiration) {
            'week'  => now()->addWeek(),
            'month' => now()->addMonth(),
            'year'  => now()->addYear(),
            default => null,
        };

        return $user->createToken($name, $scopes, $expiresAt);
    }

    /**
     * Check if the token belongs to the given user.
     *
     * @param User $user The user to check
     * @return bool Whether the token belongs to the user
     */
    public function belongsToUser(User $user): bool
    {
        return $this->tokenable_id === $user->id && $this->tokenable_type === User::class;
    }

    /**
     * Scope a query to only include tokens for a specific user.
     *
     * @param  Builder<ApiKey>  $query
     * @param  User  $user
     * @return Builder<ApiKey>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query
            ->where('tokenable_id', $user->id)
            ->where('tokenable_type', User::class);
    }
}
