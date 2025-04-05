<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Step 4 of the UUID migration sequence:
     * Convert schedule_items.schedule_id to UUID type
     * and restore foreign key constraint
     */
    public function up(): void
    {
        // Since we already dropped the foreign key in the previous migration,
        // we can directly convert the column type
        DB::statement('ALTER TABLE schedule_items ALTER COLUMN schedule_id TYPE uuid USING NULL');

        // Add the foreign key constraint back
        Schema::table('schedule_items', function (Blueprint $table) {
            $table->foreign('schedule_id')
                ->references('id')
                ->on('schedules')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot easily convert UUID back to bigint
        // This would result in data loss, so we don't provide a reversion
    }
};
