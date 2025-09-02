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
$undefined->method(); // Test failure for PHPStan Github CI Workflow
