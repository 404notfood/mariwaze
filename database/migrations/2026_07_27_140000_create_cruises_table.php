<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cruises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('departure_port');
            $table->string('destination_port');
            $table->date('departure_date');
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedSmallInteger('crew_count')->default(1);
            $table->string('navigation_style')->default('Confort');
            $table->string('status')->default('Préparation');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cruises');
    }
};
