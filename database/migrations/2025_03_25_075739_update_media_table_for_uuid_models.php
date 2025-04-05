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
        Schema::table('media', function (Blueprint $table) {
            // First drop the foreign key and index if they exist
            $table->dropMorphs('model');

            // Re-create the model_type column (no change needed)
            $table->string('model_type');

            // Re-create the model_id column as a string to support UUIDs
            $table->string('model_id', 36);

            // Add back the index
            $table->index(['model_type', 'model_id'], 'media_model_type_model_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Drop the string-based columns
            $table->dropIndex('media_model_type_model_id_index');
            $table->dropColumn(['model_type', 'model_id']);

            // Add back the original bigInteger-based columns
            $table->morphs('model');
        });
    }
};
