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
        Schema::create('class_resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('team_id');
            $table->uuid('category_id')->nullable();
            $table->uuid('created_by');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('access_level', ['all', 'teacher', 'owner'])->default('all');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('cascade');
                
            $table->foreign('category_id')
                ->references('id')
                ->on('resource_categories')
                ->onDelete('set null');
                
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_resources');
    }
}; 