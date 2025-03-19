<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('name')->unique(); // Unique role name (e.g., "Admin", "User")
            $table->timestamps(); // Created_at & updated_at timestamps
        });

        // Insert default roles
        DB::table('roles')->insert([
            ['name' => 'user'],
            ['name' => 'CSC'],
            ['name' => 'Developer'],
            ['name' => 'Administrator'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles'); // Drops the table if we roll back the migration
    }
};

