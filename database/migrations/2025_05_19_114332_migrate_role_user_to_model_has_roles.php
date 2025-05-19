<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fetch all role_user rows
        $roleUserRows = DB::table('role_user')->get();

        foreach ($roleUserRows as $row) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $row->role_id,
                'model_type' => \App\Models\User::class,
                'model_id' => $row->user_id,
            ]);
        }
    }

    public function down(): void
    {
        // Remove what was added in the up() method
        $userIds = DB::table('role_user')->pluck('user_id');

        DB::table('model_has_roles')
            ->where('model_type', \App\Models\User::class)
            ->whereIn('model_id', $userIds)
            ->delete();
    }
};
