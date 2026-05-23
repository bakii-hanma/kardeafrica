<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Relax the status / payment_status columns from ENUM to free-form strings,
 * so they can grow with the App\Models\Order constants without a migration
 * for every new state.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite enforces enums via CHECK constraints; the cleanest way
            // to drop them is to rebuild the columns.
            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('status', 'status_old');
                $table->renameColumn('payment_status', 'payment_status_old');
            });
            Schema::table('orders', function (Blueprint $table) {
                $table->string('status', 30)->default('pending')->after('user_id');
                $table->string('payment_status', 30)->default('pending')->after('status');
            });
            DB::statement('UPDATE orders SET status = status_old, payment_status = payment_status_old');
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn(['status_old', 'payment_status_old']);
            });
        } else {
            DB::statement("ALTER TABLE orders MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE orders MODIFY payment_status VARCHAR(30) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        // Pas de rollback : revenir a l'enum casserait toute donnee
        // utilisant les nouveaux statuts.
    }
};
