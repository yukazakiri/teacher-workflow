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
        Schema::create('parent_student_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->comment('Parent user ID')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->string('relationship_type')->default('parent')->comment('Type of relationship: parent, guardian, etc.');
            $table->boolean('is_primary')->default(false)->comment('Whether this is the primary relationship');
            $table->timestamps();
            
            // Each parent can only be linked to a student once
            $table->unique(['user_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_student_relationships');
    }
};
