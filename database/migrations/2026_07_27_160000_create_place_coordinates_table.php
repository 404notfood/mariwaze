<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_coordinates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('normalized_name')->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('feature_class', 1);
            $table->string('feature_code', 16);
            $table->string('country_code', 2)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_coordinates');
    }
};
