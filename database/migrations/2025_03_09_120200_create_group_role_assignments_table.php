<?php

declare(strict_types=1);

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
        Schema::create('group_role_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('group_id');
            $table->uuid('user_id');
            $table->uuid('activity_role_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('activity_role_id')
                ->references('id')
                ->on('activity_roles')
                ->onDelete('cascade');

            // A user can only have one role in a group
            $table->unique(['group_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_role_assignments');
    }
};
