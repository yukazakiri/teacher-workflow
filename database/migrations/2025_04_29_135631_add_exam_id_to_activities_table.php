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
        Schema::table('activities', function (Blueprint $table) {
            // Add nullable exam_id, constrained to exams table
            // Ensure it's placed appropriately, e.g., after activity_type_id
            $table->foreignUuid('exam_id')
                  ->nullable()
                  ->after('activity_type_id') // You might adjust 'after'
                  ->constrained('exams')      // Assuming your exams table is named 'exams'
                  ->onDelete('set null');      // Or cascade if Activity should be deleted with Exam
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Drop foreign key constraint first (important for some DBs)
            // The specific name might vary, check your DB or use convention
            // Laravel 10+ usually handles this automatically if using constrained()
            // $table->dropForeign('activities_exam_id_foreign'); 
            $table->dropConstrainedForeignId('exam_id'); // Preferred way in Laravel 10+
            
            // Then drop the column if dropConstrainedForeignId didn't
            // if (Schema::hasColumn('activities', 'exam_id')) { 
            //     $table->dropColumn('exam_id');
            // }
        });
    }
};
