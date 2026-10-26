<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('place_coordinates', function (Blueprint $table): void {
            $table->unsignedBigInteger('geoname_id')->nullable()->unique();
            $table->unsignedBigInteger('population')->default(0);
            $table->jsonb('alternate_names')->nullable();
        });
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX place_coordinates_alternate_names_gin ON place_coordinates USING GIN (alternate_names)');
        }
    }

    public function down(): void
    {
        Schema::table('place_coordinates', function (Blueprint $table): void {
            $table->dropUnique(['geoname_id']);
            $table->dropColumn(['geoname_id', 'population', 'alternate_names']);
        });
    }
};
