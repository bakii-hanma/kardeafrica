<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_order_id')->constrained()->cascadeOnDelete();
            $table->string('product_id', 50);
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('color', 7)->nullable();
            $table->decimal('unit_price',  10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('total_price', 10, 2);
            $table->timestamps();

            $table->index('reseller_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_order_items');
    }
};
