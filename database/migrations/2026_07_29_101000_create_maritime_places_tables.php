<?php

use App\Support\Postgis;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ports', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('region')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('min_depth_meters', 6, 2)->nullable();
            $table->decimal('max_depth_meters', 6, 2)->nullable();
            $table->decimal('max_length_meters', 6, 2)->nullable();
            $table->decimal('max_width_meters', 6, 2)->nullable();
            $table->unsignedSmallInteger('berths')->nullable();
            $table->boolean('has_fuel')->default(false);
            $table->boolean('has_water')->default(false);
            $table->boolean('has_electricity')->default(false);
            $table->boolean('has_shower')->default(false);
            $table->boolean('has_repair')->default(false);
            $table->boolean('has_provisions')->default(false);
            $table->string('vhf_channel')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->decimal('night_price_eur', 7, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('source')->default('osm');
            $table->string('source_ref')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('country_code');
            $table->unique(['source', 'source_ref']);
        });

        Schema::create('anchorages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('region')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('min_depth_meters', 6, 2)->nullable();
            $table->decimal('max_depth_meters', 6, 2)->nullable();
            $table->string('bottom_type')->nullable();
            $table->string('holding')->nullable();
            /** Secteurs cardinaux protégés, ex. ["N","NE","NW"]. */
            $table->json('protected_from')->nullable();
            $table->string('exposure')->nullable();
            $table->json('tags')->nullable();
            $table->json('warnings')->nullable();
            $table->text('description')->nullable();
            $table->string('source')->default('osm');
            $table->string('source_ref')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('country_code');
            $table->unique(['source', 'source_ref']);
        });

        Postgis::addPointColumn('ports');
        Postgis::addPointColumn('anchorages');
    }

    public function down(): void
    {
        Schema::dropIfExists('anchorages');
        Schema::dropIfExists('ports');
    }
};
