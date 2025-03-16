<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing team_user table
        Schema::dropIfExists('team_user');

        // Recreate the team_user table with UUID for user_id
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new team_user table
        Schema::dropIfExists('team_user');

        // Recreate the original team_user table with bigint for user_id
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id');
            $table->string('role')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });
    }
};
