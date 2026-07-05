<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_chart_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_appropriation_id')->constrained('budget_appropriations')->cascadeOnDelete();
            $table->foreignId('expense_chart_account_id')->constrained('chart_accounts')->restrictOnDelete();
            $table->foreignId('payable_chart_account_id')->constrained('chart_accounts')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'budget_appropriation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_chart_mappings');
    }
};
