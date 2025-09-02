<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperRole
 */
class Role extends SpatieRole
{

    /**
     * This will compile but PHPStan will complain about wrong return type
     *
     * @return string
     */
    public function testMethod(): int
    {
        return 123; // PHPStan expects string but we return int
    }

    protected $fillable = ['name', 'guard_name'];

    /**
     * Define the many-to-many relationship with users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
