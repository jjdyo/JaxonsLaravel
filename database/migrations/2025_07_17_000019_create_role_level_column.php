<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the default level from config
        $defaultLevel = config('role.hierarchy.user', 1);

        // Add the role_level column after email
        Schema::table('users', function (Blueprint $table) use ($defaultLevel) {
            $table->integer('role_level')->default($defaultLevel)->after('email_verified_at')->index();
        });

        // Backfill existing users with their role levels
        $roleHierarchy = config('role.hierarchy', []);

        if (!empty($roleHierarchy)) {
            foreach ($roleHierarchy as $roleName => $level) {
                User::where('role', $roleName)->update(['role_level' => $level]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_level');
        });
    }
};
