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
        Schema::create('shopping_cart', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable(); // Pour les utilisateurs non connectés
            // FK conditionnelle (M14) : sur SQLite (tests), on NE pose PAS la
            // contrainte — sinon la migration suivante qui supprime cette colonne
            // casse (SQLite recrée la table en gardant une FK vers une colonne
            // disparue). En MySQL/prod, comportement strictement inchangé.
            $table->unsignedBigInteger('card_id');
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->foreign('card_id')->references('id')->on('cards')->onDelete('cascade');
            }
            $table->integer('quantity');
            $table->timestamps();
            
            $table->index(['user_id', 'card_id']);
            $table->index(['session_id', 'card_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_cart');
    }
};
