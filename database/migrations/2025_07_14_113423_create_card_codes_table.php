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
        Schema::create('card_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('set null');
            $table->string('code')->unique();
            $table->decimal('value', 10, 2);
            $table->string('currency', 3)->default('XOF');
            $table->enum('status', ['available', 'sold', 'used', 'expired'])->default('available');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->json('metadata')->nullable(); // Informations supplémentaires spécifiques à la carte
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_codes');
    }
};
