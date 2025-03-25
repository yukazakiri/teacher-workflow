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
     * Step 2 of the UUID migration sequence:
     * Make schedule_id nullable to prepare for UUID conversion
     */
    public function up(): void
    {
        // Using a raw SQL statement to avoid type casting issues
        DB::statement('ALTER TABLE schedule_items ALTER COLUMN schedule_id DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Using a raw SQL statement to revert the change
        DB::statement('ALTER TABLE schedule_items ALTER COLUMN schedule_id SET NOT NULL');
    }
};
