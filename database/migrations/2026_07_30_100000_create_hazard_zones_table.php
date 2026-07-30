<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hazard_zones', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            /** Catégorie : military, orca, piracy, traffic, nature. */
            $table->string('category');
            /** Sévérité : info, warning, danger. */
            $table->string('severity');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            /** Rayon d'influence de la zone, en milles nautiques. */
            $table->decimal('radius_nm', 7, 2);
            $table->text('description');
            $table->text('advice')->nullable();
            /** Mois où le risque est le plus marqué (1-12) ; null = toute l'année. */
            $table->json('months')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hazard_zones');
    }
};
