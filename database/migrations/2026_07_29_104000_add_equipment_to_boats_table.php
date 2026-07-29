<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boats', function (Blueprint $table): void {
            /** Équipements de sécurité présents à bord, ex. {"vhf": true, "ais": false}. */
            $table->json('equipment_json')->nullable()->after('fuel_consumption_per_hour');
        });
    }

    public function down(): void
    {
        Schema::table('boats', function (Blueprint $table): void {
            $table->dropColumn('equipment_json');
        });
    }
};
