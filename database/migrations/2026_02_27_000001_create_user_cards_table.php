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
        Schema::create('user_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('set null');
            $table->string('product_id')->nullable(); // API product ID (string)
            $table->unsignedBigInteger('checkout_card_id')->nullable(); // ID from checkout API response
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('card_code'); // The actual gift card code
            $table->string('pin')->nullable(); // PIN if provided
            $table->date('expiration_date')->nullable();
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->decimal('face_value', 10, 2)->default(0);
            $table->string('currency', 5)->default('XAF');
            $table->string('image_url')->nullable();
            $table->json('metadata')->nullable(); // Store extra API response data
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_cards');
    }
};
