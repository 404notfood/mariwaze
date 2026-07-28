<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('navigation_area')->default('coastal');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('checklist_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('checklist_section_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('quantity_formula')->nullable();
            $table->boolean('required')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('cruise_checklists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cruise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('cruise_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cruise_checklist_id')->constrained()->cascadeOnDelete();
            $table->string('section');
            $table->string('label');
            $table->string('quantity')->nullable();
            $table->boolean('required')->default(true);
            $table->boolean('checked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cruise_checklist_items');
        Schema::dropIfExists('cruise_checklists');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklist_sections');
        Schema::dropIfExists('checklist_templates');
    }
};
