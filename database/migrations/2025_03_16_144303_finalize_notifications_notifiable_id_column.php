<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, ensure the column is properly typed as UUID
        if (DB::connection()->getDriverName() === 'pgsql') {
            // Make sure the column is UUID type and not nullable
            Schema::table('notifications', function (Blueprint $table) {
                // First check if the column exists and is already UUID
                $columnType = DB::select("
                    SELECT data_type 
                    FROM information_schema.columns 
                    WHERE table_name = 'notifications' 
                    AND column_name = 'notifiable_id'
                ");

                // If the column is not UUID or doesn't exist, create/modify it
                if (empty($columnType) || $columnType[0]->data_type !== 'uuid') {
                    // If column exists, drop it first
                    if (! empty($columnType)) {
                        $table->dropColumn('notifiable_id');
                    }

                    // Create the column as UUID
                    $table->uuid('notifiable_id')->nullable();
                }
            });

            // Add an index for better performance
            Schema::table('notifications', function (Blueprint $table) {
                // Check if index exists before creating
                $indexExists = DB::select("
                    SELECT indexname 
                    FROM pg_indexes 
                    WHERE tablename = 'notifications' 
                    AND indexname = 'notifications_notifiable_id_index'
                ");

                if (empty($indexExists)) {
                    $table->index('notifiable_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this migration as it's just ensuring the correct structure
    }
};
