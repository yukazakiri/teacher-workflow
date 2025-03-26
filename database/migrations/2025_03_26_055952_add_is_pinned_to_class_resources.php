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
        Schema::table('class_resources', function (Blueprint $table) {
            if (!Schema::hasColumn('class_resources', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('access_level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_resources', function (Blueprint $table) {
            if (Schema::hasColumn('class_resources', 'is_pinned')) {
                $table->dropColumn('is_pinned');
            }
        });
    }
};
