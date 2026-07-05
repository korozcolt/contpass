<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_appropriations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('code', 30);
            $table->string('name');
            $table->decimal('initial_amount', 18, 2)->default(0);
            $table->decimal('additions', 18, 2)->default(0);
            $table->decimal('reductions', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'fiscal_year', 'code']);
            $table->index(['company_id', 'fiscal_year', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_appropriations');
    }
};
