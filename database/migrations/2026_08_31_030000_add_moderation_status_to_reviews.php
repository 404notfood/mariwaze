<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->after('visited_on');
            $table->foreignId('moderated_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            $table->index(['status', 'created_at']);
        });

        DB::table('reviews')->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex(['status', 'created_at']);
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['status', 'moderated_at']);
        });
    }
};
