<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_password_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('new_password'); // already hashed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_password_changes');
    }
};
