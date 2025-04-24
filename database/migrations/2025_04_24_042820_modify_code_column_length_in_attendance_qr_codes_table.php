<?php

declare(strict_types=1);

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
        Schema::table('attendance_qr_codes', function (Blueprint $table) {
            // Change the column length to 64
            $table->string('code', 64)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_qr_codes', function (Blueprint $table) {
            // Revert the column length back to 32 if needed
            $table->string('code', 32)->change();
        });
    }
};
