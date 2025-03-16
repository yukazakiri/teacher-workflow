<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to handle UUID differently
        if (DB::connection()->getDriverName() === 'pgsql') {
            // First, drop any foreign keys that might reference this column
            $foreignKeys = $this->getForeignKeysForColumn('notifications', 'notifiable_id');
            foreach ($foreignKeys as $foreignKey) {
                Schema::table('notifications', function (Blueprint $table) use ($foreignKey) {
                    $table->dropForeign($foreignKey);
                });
            }

            // Add a temporary UUID column
            Schema::table('notifications', function (Blueprint $table) {
                $table->uuid('notifiable_id_uuid')->nullable();
            });

            // Update the temporary column with UUID values from the users table
            $notifications = DB::table('notifications')
                ->where('notifiable_type', 'App\\Models\\User')
                ->get();

            foreach ($notifications as $notification) {
                // Find the user with this ID
                $user = DB::table('users')->where('id', $notification->notifiable_id)->first();
                
                if ($user) {
                    DB::table('notifications')
                        ->where('id', $notification->id)
                        ->update(['notifiable_id_uuid' => $user->id]);
                }
            }

            // Drop the old column and rename the new one
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('notifiable_id');
            });

            Schema::table('notifications', function (Blueprint $table) {
                $table->renameColumn('notifiable_id_uuid', 'notifiable_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a complex operation that might cause data loss
        // It's better to handle this manually if needed
    }

    /**
     * Get foreign keys that reference a specific column.
     */
    private function getForeignKeysForColumn(string $table, string $column): array
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return [];
        }

        $foreignKeys = [];
        $constraints = DB::select(
            "SELECT conname 
             FROM pg_constraint 
             WHERE conrelid = (SELECT oid FROM pg_class WHERE relname = ?) 
             AND contype = 'f'",
            [$table]
        );

        foreach ($constraints as $constraint) {
            // Check if this constraint references our column
            $columnCheck = DB::select(
                "SELECT a.attname 
                 FROM pg_attribute a 
                 JOIN pg_constraint c ON a.attnum = ANY(c.conkey) 
                 WHERE c.conname = ? AND a.attrelid = (SELECT oid FROM pg_class WHERE relname = ?)",
                [$constraint->conname, $table]
            );

            foreach ($columnCheck as $col) {
                if ($col->attname === $column) {
                    $foreignKeys[] = $constraint->conname;
                    break;
                }
            }
        }

        return $foreignKeys;
    }
};
