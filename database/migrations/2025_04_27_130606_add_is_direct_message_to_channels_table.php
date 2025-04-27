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
        Schema::table('channels', function (Blueprint $table) {
            $table->boolean('is_dm')->default(false)->after('is_private')->index();
            // Direct message channels won't need a category or a public name/slug/description
            $table->foreignUuid('category_id')->nullable()->change();
            $table->string('name')->nullable()->change();
            $table->string('slug')->nullable()->change();
            $table->text('description')->nullable()->change();
            // Make position nullable as DMs might not need explicit positioning
            $table->integer('position')->nullable()->change(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('is_dm');
            // Revert nullable changes - assumes original state was NOT nullable
            // If any of these were originally nullable, adjust accordingly
            $table->foreignUuid('category_id')->nullable(false)->change();
            $table->string('name')->nullable(false)->change();
            $table->string('slug')->nullable(false)->change(); 
            $table->text('description')->nullable()->change(); // Description might have been nullable
            $table->integer('position')->nullable(false)->change(); 
        });
    }
};
