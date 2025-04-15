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
        Schema::create('channel_permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('team_id');
            $table->string('role');
            $table->json('permissions');
            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->unique(['team_id', 'role']);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_permissions');

        if (Schema::hasColumn('channel_members', 'permissions')) {
            Schema::table('channel_members', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }
    }
};
