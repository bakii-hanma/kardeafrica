<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code', 20)->unique();          // Code de connexion court (ex: KA-V-7H3K)
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->decimal('wallet_balance',  10, 2)->default(0);     // Plafond logique 150 000 FCFA
            $table->decimal('max_wallet',      10, 2)->default(150000);
            $table->decimal('commission_rate', 5,  2)->default(3.00);  // 3 % par défaut
            $table->decimal('total_commission_earned', 12, 2)->default(0);
            $table->decimal('total_volume',          12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['vendor_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resellers');
    }
};
