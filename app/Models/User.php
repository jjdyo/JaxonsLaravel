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
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

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
        ];
    }

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
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }
}
