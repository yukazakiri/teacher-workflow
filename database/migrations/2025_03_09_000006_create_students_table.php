no<?php
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
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('student_id')->nullable(); // For school-assigned student ID
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active'); // active, inactive, graduated
            $table->timestamps();
            $table
                ->foreignUuid('team_id')
                ->nullable()
                ->constrained('teams')
                ->cascadeOnDelete();

            // Add unique constraint for team_id and email combination
            // $table->unique(["team_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
