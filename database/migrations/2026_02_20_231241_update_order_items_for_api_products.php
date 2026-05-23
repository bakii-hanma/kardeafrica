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
        Schema::table('order_items', function (Blueprint $table) {
            // Drop the foreign key constraint on card_id
            $table->dropForeign(['card_id']);
            
            // Modify card_id to be nullable
            $table->unsignedBigInteger('card_id')->nullable()->change();
            
            // Add new columns for API products
            $table->string('product_id')->nullable()->after('order_id');
            $table->string('name')->nullable()->after('product_id');
            $table->string('image_url')->nullable()->after('total_price');
            
            // Modify card_details to be nullable
            $table->json('card_details')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Revert changes
            $table->dropColumn(['product_id', 'name', 'image_url']);
            
            // Since we can't easily revert nullable to not nullable if there are nulls,
            // we will just add the constraint back assuming data is valid or truncated.
            // This is a simplified down method.
            
            // Make card_id not nullable (might fail if nulls exist)
            // $table->unsignedBigInteger('card_id')->nullable(false)->change();
            
            // Add foreign key back
            $table->foreign('card_id')->references('id')->on('cards')->onDelete('cascade');
        });
    }
};
