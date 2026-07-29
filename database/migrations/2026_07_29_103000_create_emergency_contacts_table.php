<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('country_code', 2);
            $table->string('country_name');
            /** CROSS, MRCC, secours médical, autorité portuaire… */
            $table->string('authority');
            $table->string('label');
            $table->string('phone')->nullable();
            $table->string('vhf_channel')->nullable();
            $table->string('note')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['country_code', 'position']);
        });

        Schema::create('country_regulations', function (Blueprint $table): void {
            $table->id();
            $table->string('country_code', 2)->unique();
            $table->string('country_name');
            $table->string('flag_emoji')->nullable();
            /** Documents exigés à bord, ex. ["Permis côtier", "Assurance RC"]. */
            $table->json('required_documents')->nullable();
            $table->text('rules')->nullable();
            $table->text('anchoring_rules')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_regulations');
        Schema::dropIfExists('emergency_contacts');
    }
};
