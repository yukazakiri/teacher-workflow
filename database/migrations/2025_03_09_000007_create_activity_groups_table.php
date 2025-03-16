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
        Schema::create('activity_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('activity_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });

        Schema::create('group_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('activity_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });

        Schema::create('student_group_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('activity_group_id');
            $table->uuid('group_role_id')->nullable();
            $table->timestamps();
            
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('activity_group_id')->references('id')->on('activity_groups')->onDelete('cascade');
            $table->foreign('group_role_id')->references('id')->on('group_roles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_group_assignments');
        Schema::dropIfExists('group_roles');
        Schema::dropIfExists('activity_groups');
    }
};
