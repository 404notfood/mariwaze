<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cruises', function (Blueprint $table): void {
            $table->string('route_generation_status')->default('idle')->after('status')->index();
            $table->uuid('route_generation_token')->nullable()->after('route_generation_status');
            $table->text('route_generation_error')->nullable()->after('route_generation_token');
            $table->timestamp('route_generation_started_at')->nullable()->after('route_generation_error');
            $table->timestamp('route_generation_completed_at')->nullable()->after('route_generation_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('cruises', function (Blueprint $table): void {
            $table->dropIndex(['route_generation_status']);
            $table->dropColumn([
                'route_generation_status',
                'route_generation_token',
                'route_generation_error',
                'route_generation_started_at',
                'route_generation_completed_at',
            ]);
        });
    }
};
