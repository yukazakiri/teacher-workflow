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
            $table->string('submission_type')->default('resource')->after('status');
            $table->boolean('allow_file_uploads')->default(true)->after('submission_type');
            $table->json('allowed_file_types')->nullable()->after('allow_file_uploads');
            $table->integer('max_file_size')->default(10)->after('allowed_file_types'); // Default 10MB
            $table->boolean('allow_teacher_submission')->default(false)->after('max_file_size');
            $table->json('form_structure')->nullable()->after('allow_teacher_submission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn([
                'submission_type',
                'allow_file_uploads',
                'allowed_file_types',
                'max_file_size',
                'allow_teacher_submission',
                'form_structure',
            ]);
        });
    }
}; 