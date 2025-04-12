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
        // We can't create a simple index on JSON column in PostgreSQL
        // Let's use other indexes for channel access checks
        Schema::table('channels', function (Blueprint $table) {
            $table->index(['team_id', 'category_id']);
            $table->index('is_private');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'category_id']);
            $table->dropIndex(['is_private']);
            $table->dropIndex(['type']);
        });
    }
};
