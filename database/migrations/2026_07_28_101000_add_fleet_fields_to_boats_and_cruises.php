<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boats', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('fleet_status')->default('available')->after('fuel_range');
            $table->unsignedSmallInteger('berths')->nullable()->after('fleet_status');
            $table->decimal('fuel_consumption_per_hour', 5, 2)->nullable()->after('berths');
        });

        Schema::table('cruises', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('boat_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->string('navigation_area')->default('coastal')->after('navigation_style');
        });
    }

    public function down(): void
    {
        Schema::table('cruises', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('boat_id');
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn('navigation_area');
        });

        Schema::table('boats', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['fleet_status', 'berths', 'fuel_consumption_per_hour']);
        });
    }
};
