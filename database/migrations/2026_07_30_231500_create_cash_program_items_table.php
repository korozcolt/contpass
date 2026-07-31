<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_program_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('fiscal_year')
                ->comment('Vigencia fiscal de la Programación Anual de Caja (P.A.C.)');

            $table->string('movement_type')
                ->comment('income: proyección de recaudo | expense: proyección de pago, por rubro');

            $table->foreignId('budget_appropriation_id')
                ->nullable()
                ->constrained('budget_appropriations')
                ->cascadeOnDelete();

            $table->foreignId('budget_revenue_id')
                ->nullable()
                ->constrained('budget_revenues')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('month')
                ->comment('Mes de la vigencia fiscal, 1-12');

            $table->decimal('projected_amount', 15, 2)
                ->comment('Monto de caja proyectado para el rubro en el mes');

            $table->timestamps();

            $table->index(['company_id', 'fiscal_year', 'movement_type', 'month'], 'cash_program_items_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_program_items');
    }
};
