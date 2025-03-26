<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Step 3 of the UUID migration sequence:
     * Convert schedules.id to UUID type
     */
    public function up(): void
    {
        // First, ensure UUID extension is installed
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        
        // First, drop any foreign keys referencing schedules.id
        Schema::table('schedule_items', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
        });
        
        // Next, drop the primary key constraint and any sequence dependency
        DB::statement('ALTER TABLE schedules DROP CONSTRAINT schedules_pkey');
        DB::statement('ALTER TABLE schedules ALTER COLUMN id DROP DEFAULT');
        
        // Drop the autoincrement sequence if it exists
        DB::statement('DROP SEQUENCE IF EXISTS schedules_id_seq CASCADE');
        
        // Convert the column to UUID
        DB::statement('ALTER TABLE schedules ALTER COLUMN id TYPE uuid USING uuid_generate_v4()');
        
        // Add the primary key constraint back
        DB::statement('ALTER TABLE schedules ADD PRIMARY KEY (id)');

        // Update Schedule model to use HasUuids trait
        // This needs to be done manually in the model
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
