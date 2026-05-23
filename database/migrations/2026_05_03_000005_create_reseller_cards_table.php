<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reseller_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_id', 50)->nullable();
            $table->unsignedBigInteger('checkout_card_id')->nullable();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('card_code');
            $table->string('pin')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('status', 20)->default('active');     // active | used | expired
            $table->decimal('face_value', 10, 2)->default(0);
            $table->string('currency', 5)->default('XAF');
            $table->string('image_url', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('reseller_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_cards');
    }
};
