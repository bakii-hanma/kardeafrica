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
        Schema::table('shopping_cart', function (Blueprint $table) {
            // Drop indexes referencing card_id (required for SQLite)
            $table->dropIndex(['user_id', 'card_id']);
            $table->dropIndex(['session_id', 'card_id']);

            // Drop the foreign key and column for card_id
            // (FK skipped on SQLite — only needs the column drop)
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['card_id']);
            }
            $table->dropColumn('card_id');
        });

        Schema::table('shopping_cart', function (Blueprint $table) {
            // Add fields for API products
            $table->string('product_id')->after('session_id');
            $table->string('name')->after('product_id');
            $table->decimal('price', 10, 2)->after('name');
            $table->string('image_url')->nullable()->after('price');

            $table->index(['user_id', 'product_id']);
            $table->index(['session_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopping_cart', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'product_id']);
            $table->dropIndex(['session_id', 'product_id']);
            $table->dropColumn(['product_id', 'name', 'price', 'image_url']);
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->index(['user_id', 'card_id']);
            $table->index(['session_id', 'card_id']);
        });
    }
};
