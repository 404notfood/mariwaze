<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waterway_nodes', function (Blueprint $table): void {
            $table->id();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
            $table->unique(['latitude', 'longitude']);
        });

        Schema::create('waterway_edges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('from_node_id')->constrained('waterway_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('waterway_nodes')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->decimal('distance_meters', 10, 2);
            $table->unsignedSmallInteger('lock_count')->default(0);
            $table->decimal('min_depth_meters', 6, 2)->nullable();
            $table->decimal('max_air_draft_meters', 6, 2)->nullable();
            $table->decimal('max_length_meters', 7, 2)->nullable();
            $table->decimal('max_width_meters', 6, 2)->nullable();
            $table->timestamps();
            $table->index('from_node_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waterway_edges');
        Schema::dropIfExists('waterway_nodes');
    }
};
