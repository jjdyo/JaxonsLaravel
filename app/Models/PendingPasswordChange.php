<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPendingPasswordChange
 */
class PendingPasswordChange extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'new_password',
    ];

    /**
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
