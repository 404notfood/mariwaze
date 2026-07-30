<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seamarks', function (Blueprint $table): void {
            $table->id();
            /** Référence OSM « node/123 », pour les mises à jour d'import. */
            $table->string('source_ref')->unique();
            $table->string('name')->nullable();
            /** Catégorie normalisée : cardinal_north, lateral_port, lighthouse… */
            $table->string('category');
            /** Tag seamark:type d'origine. */
            $table->string('seamark_type');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('category');
        });

        Schema::table('hazard_zones', function (Blueprint $table): void {
            /** Contour réel de la zone : liste de sommets {latitude, longitude}. */
            $table->json('geometry')->nullable();
            /** Origine de la zone (seed ou import OSM). */
            $table->string('source_ref')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('hazard_zones', function (Blueprint $table): void {
            $table->dropColumn(['geometry', 'source_ref']);
        });
        Schema::dropIfExists('seamarks');
    }
};
