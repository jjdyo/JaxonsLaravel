<?php

namespace App\Models;

use App\Notifications\ResetPasswordEmail;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
/**
 * @method \Illuminate\Database\Eloquent\Relations\MorphMany<\Laravel\Sanctum\PersonalAccessToken, User> tokens()
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles {
            assignRole as protected traitAssignRole;
            syncRoles as protected traitSyncRoles;
            removeRole as protected traitRemoveRole;
        }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role_level' => 'integer',
        ];
    }

    /**
     * Section: Query Scopes
     * Brief: Common reusable database filters for users (searching, sorting, verification, roles, listings).
     */

    /**
     * Query scope: only users with a verified email.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeVerified(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Query scope: only users without a verified email.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeUnverified(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('email_verified_at');
    }

    /**
     * Query scope: only admin users (wraps Spatie's role scope).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeAdmins(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        // Using Spatie\Permission's provided role() scope
        return $query->role('admin');
    }

    /**
     * Query scope: select only summary columns commonly used in listings.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeSelectSummary(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->select(['id', 'name', 'email', 'email_verified_at', 'created_at']);
    }

    /**
     * Query scope: eager-load roles with minimal columns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeWithRolesMinimal(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->with(['roles:id,name']);
    }

    /**
     * Query scope: case-insensitive search by name or email.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @param  string  $term
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, string $term): \Illuminate\Database\Eloquent\Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }
        $like = '%' . mb_strtolower($term) . '%';
        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($like) {
            $q->whereRaw('LOWER(name) LIKE ?', [$like])
              ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
        });
    }

    /**
     * Query scope: order by name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @param  'asc'|'desc' $direction
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeOrderByName(\Illuminate\Database\Eloquent\Builder $query, string $direction = 'asc'): \Illuminate\Database\Eloquent\Builder
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        return $query->orderBy('name', $direction);
    }

    /**
     * Query scope: order by most recently created.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeNewest(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Section: Role Hierarchy & Level Sync
     * Brief: Helpers for computing and maintaining a user's highest role level and syncing the denormalized role_level column.
     */

    /**
     * Highest role level for this user based on configured role hierarchy.
     */
    public function highestRoleLevel(): int
    {
        return \App\Services\Authorization\RoleHierarchy::highestLevelForUser($this);
    }

    /**
     * Recalculate and persist the user's role_level based on current roles.
     */
    public function refreshRoleLevel(): void
    {
        $level = $this->highestRoleLevel();
        // Update only if changed to avoid unnecessary writes
        if ((int)($this->role_level ?? 0) !== $level) {
            $this->forceFill(['role_level' => $level]);
            // Save quietly to avoid triggering observers infinitely
            $this->saveQuietly();
        }
    }

    /**
     * Wrap Spatie assignRole to also update the denormalized role_level column.
     *
     * @param array|\BackedEnum|\Illuminate\Support\Collection|int|\Spatie\Permission\Contracts\Role|string ...$roles
     * @phpstan-param (\BackedEnum|int|string|\Spatie\Permission\Contracts\Role|list<\BackedEnum|int|string|\Spatie\Permission\Contracts\Role>|\Illuminate\Support\Collection<array-key, \BackedEnum|int|string|\Spatie\Permission\Contracts\Role>) ...$roles
     * @return $this
     */
    public function assignRole(array|\BackedEnum|\Illuminate\Support\Collection|int|\Spatie\Permission\Contracts\Role|string ...$roles): self
    {
        $this->traitAssignRole(...$roles);
        $this->refreshRoleLevel();
        return $this;
    }

    /**
     * Wrap Spatie syncRoles to also update role_level.
     *
     * @param array|\BackedEnum|\Illuminate\Support\Collection|int|\Spatie\Permission\Contracts\Role|string ...$roles
     * @phpstan-param (\BackedEnum|int|string|\Spatie\Permission\Contracts\Role|list<\BackedEnum|int|string|\Spatie\Permission\Contracts\Role>|\Illuminate\Support\Collection<array-key, \BackedEnum|int|string|\Spatie\Permission\Contracts\Role>) ...$roles
     * @return $this
     */
    public function syncRoles(array|\BackedEnum|\Illuminate\Support\Collection|int|\Spatie\Permission\Contracts\Role|string ...$roles): self
    {
        $this->traitSyncRoles(...$roles);
        $this->refreshRoleLevel();
        return $this;
    }

    /**
     * Wrap Spatie removeRole to also update role_level.
     * @param string|\Spatie\Permission\Contracts\Role $role
     * @return $this
     */
    public function removeRole($role): self
    {
        $this->traitRemoveRole($role);
        $this->refreshRoleLevel();
        return $this;
    }

    /**
     * Section: Authorization Helpers
     * Brief: Convenience methods and scopes that encapsulate authorization rules based on role hierarchy.
     */

    /**
     * Determine if this user (actor) can manage the given target user via role hierarchy.
     */
    public function canManageUser(?self $target): bool
    {
        if ($target === null) { return false; }
        return \App\Services\Authorization\RoleHierarchy::canManageUser($this, $target);
    }

    /**
     * Scope: users manageable by the given actor based on role hierarchy.
     * Fully SQL-driven using the denormalized users.role_level column for pagination safety and performance.
     *
     * @param \Illuminate\Database\Eloquent\Builder<User> $query
     * @param User $actor
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeManageableBy(\Illuminate\Database\Eloquent\Builder $query, User $actor): \Illuminate\Database\Eloquent\Builder
    {
        // Use computed highest level to avoid relying on potentially stale column on the actor
        $actorLevel = $actor->highestRoleLevel();
        if ($actorLevel <= 0) {
            return $query->whereRaw('1=0'); // actor with no roles manages nobody
        }

        // Exclude self from manageable list for safety, even for max-level users
        return $query
            ->where('users.id', '!=', $actor->id)
            ->where('users.role_level', '<', $actorLevel);
    }


    /**
     * Section: Notifications
     * Brief: Outbound notifications to the user (password reset, email verification).
     */

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordEmail($token));
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Section: Account Rules
     * Brief: In-model rules around account lifecycle actions that are not full authorization checks.
     */

    /**
     * Determine if this user can be deleted by the given actor.
     *
     * Rules (minimal): a user cannot delete themselves.
     * Additional authorization should be handled via policies/gates.
     */
    public function canBeDeletedBy(?self $actor): bool
    {
        if ($actor === null) {
            return false;
        }
        return $actor->id !== $this->id;
    }

    /**
     * Section: Email Verification Helpers
     * Brief: Conveniences for toggling the user's email verification state.
     */

    /**
     * Mark the user's email as verified if not already.
     */
    public function markEmailVerified(): void
    {
        if (!$this->hasVerifiedEmail()) {
            $this->email_verified_at = now();
            $this->save();
        }
    }

    /**
     * Mark the user's email as unverified.
     */
    public function markEmailUnverified(): void
    {
        $this->email_verified_at = null;
        $this->save();
    }
}
