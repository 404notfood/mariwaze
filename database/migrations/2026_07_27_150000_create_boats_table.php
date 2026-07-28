<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('boat_type');
            $table->string('flag')->nullable();
            $table->string('mmsi', 9)->nullable();
            $table->decimal('length', 5, 2);
            $table->decimal('width', 5, 2);
            $table->decimal('draft', 4, 2);
            $table->decimal('mast_height', 5, 2)->nullable();
            $table->decimal('avg_speed', 4, 1);
            $table->unsignedInteger('fuel_range')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boats');
    }
};
