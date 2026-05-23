<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->decimal('commission_balance', 12, 2)->default(0)->after('wallet_balance');
        });

        Schema::table('reseller_wallet_transactions', function (Blueprint $table) {
            // 'sales' = portefeuille de vente (achats clients) ; 'commission' = portefeuille des commissions
            $table->string('wallet', 16)->default('sales')->after('reseller_id')->index();
        });

        // Backfill : les anciennes lignes 'commission' appartiennent au portefeuille 'commission'
        DB::table('reseller_wallet_transactions')
            ->where('type', 'commission')
            ->update(['wallet' => 'commission']);
    }

    public function down(): void
    {
        Schema::table('reseller_wallet_transactions', function (Blueprint $table) {
            $table->dropIndex(['wallet']);
            $table->dropColumn('wallet');
        });

        Schema::table('resellers', function (Blueprint $table) {
            $table->dropColumn('commission_balance');
        });
    }
};
