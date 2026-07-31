<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_segments', function (Blueprint $table): void {
            /** Profondeur minimale rencontrée le long de l'étape (m, EMODnet). */
            $table->decimal('min_depth_meters', 7, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('route_segments', function (Blueprint $table): void {
            $table->dropColumn('min_depth_meters');
        });
    }
};
