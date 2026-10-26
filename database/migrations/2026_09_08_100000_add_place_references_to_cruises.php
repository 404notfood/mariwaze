<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cruises', function (Blueprint $table): void {
            $table->string('departure_place', 80)->nullable();
            $table->string('destination_place', 80)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cruises', function (Blueprint $table): void {
            $table->dropColumn(['departure_place', 'destination_place']);
        });
    }
};
