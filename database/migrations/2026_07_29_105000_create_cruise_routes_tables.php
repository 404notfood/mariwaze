<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cruise_routes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cruise_id')->constrained()->cascadeOnDelete();
            /** Scénario du moteur : fast, comfort, discovery. */
            $table->string('scenario');
            $table->string('label');
            $table->boolean('is_selected')->default(false);
            $table->unsignedInteger('distance_nm')->default(0);
            $table->unsignedSmallInteger('duration_days')->default(0);
            $table->unsignedSmallInteger('stops')->default(0);
            $table->unsignedTinyInteger('score')->nullable();
            /** Détail du score : météo, bateau, sécurité, confort, communauté. */
            $table->json('score_breakdown')->nullable();
            $table->json('geometry')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['cruise_id', 'scenario']);
        });

        Schema::create('route_segments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cruise_route_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->unsignedSmallInteger('day');
            $table->string('from_name');
            $table->string('to_name');
            $table->decimal('from_latitude', 10, 7)->nullable();
            $table->decimal('from_longitude', 10, 7)->nullable();
            $table->decimal('to_latitude', 10, 7)->nullable();
            $table->decimal('to_longitude', 10, 7)->nullable();
            $table->foreignId('port_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('anchorage_id')->nullable()->constrained('anchorages')->nullOnDelete();
            $table->decimal('distance_nm', 8, 2)->default(0);
            $table->decimal('duration_hours', 6, 2)->nullable();
            $table->string('overnight')->nullable();
            $table->unsignedTinyInteger('weather_score')->nullable();
            $table->json('weather_snapshot')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['cruise_route_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_segments');
        Schema::dropIfExists('cruise_routes');
    }
};
