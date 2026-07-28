<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('audience')->default('personal');
            $table->unsignedInteger('monthly_price_cents')->default(0);
            $table->unsignedInteger('yearly_price_cents')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->unsignedSmallInteger('boat_limit')->nullable();
            $table->unsignedSmallInteger('member_limit')->nullable();
            $table->string('stripe_product_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('navigation');
            $table->text('description')->nullable();
            $table->boolean('is_billable_option')->default(false);
            $table->timestamps();
        });

        Schema::create('plan_feature', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_feature_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('included_quantity')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'plan_feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_feature');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
