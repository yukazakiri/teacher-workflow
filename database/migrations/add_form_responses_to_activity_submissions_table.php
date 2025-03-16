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
        Schema::table('activity_submissions', function (Blueprint $table) {
            $table->json('form_responses')->nullable()->after('content');
            $table->boolean('submitted_by_teacher')->default(false)->after('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_submissions', function (Blueprint $table) {
            $table->dropColumn(['form_responses', 'submitted_by_teacher']);
        });
    }
}; 