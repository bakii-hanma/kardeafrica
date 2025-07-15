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
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom de la carte
            $table->string('type'); // Type de carte (cadeau, prépayée, etc.)
            $table->decimal('value', 10, 2); // Valeur de la carte
            $table->decimal('balance', 10, 2); // Solde restant
            $table->string('currency', 3)->default('EUR'); // Devise
            $table->string('code')->unique(); // Code unique de la carte
            $table->string('status')->default('active'); // Statut: active, expired, used
            $table->text('description')->nullable(); // Description de la carte
            $table->string('image')->nullable(); // Image de la carte
            $table->string('brand')->nullable(); // Marque de la carte
            $table->date('expiry_date')->nullable(); // Date d'expiration
            $table->unsignedBigInteger('user_id')->nullable(); // Propriétaire de la carte
            $table->json('metadata')->nullable(); // Données supplémentaires
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'expiry_date']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
