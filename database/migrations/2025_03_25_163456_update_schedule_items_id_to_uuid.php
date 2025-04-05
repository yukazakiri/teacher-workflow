<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Step 4 of the UUID migration sequence:
     * Convert schedule_items.id to UUID type
     */
    public function up(): void
    {
        // First, drop the primary key constraint and any sequence dependency
        DB::statement('ALTER TABLE schedule_items DROP CONSTRAINT schedule_items_pkey');
        DB::statement('ALTER TABLE schedule_items ALTER COLUMN id DROP DEFAULT');

        // Drop the autoincrement sequence if it exists
        DB::statement('DROP SEQUENCE IF EXISTS schedule_items_id_seq CASCADE');

        // Convert the column to UUID
        DB::statement('ALTER TABLE schedule_items ALTER COLUMN id TYPE uuid USING NULL');

        // Add the primary key constraint back
        DB::statement('ALTER TABLE schedule_items ADD PRIMARY KEY (id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot easily convert UUID back to autoincrementing bigint
        // This would result in data loss, so we don't provide a reversion
    }
};
