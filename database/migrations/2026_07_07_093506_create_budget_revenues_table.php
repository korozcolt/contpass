<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_revenues', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('fiscal_year')
                ->comment('Vigencia fiscal presupuestal');

            $table->string('code')
                ->comment('Código del rubro de ingresos. Ej: 1.1.01');

            $table->string('name')
                ->comment('Nombre del rubro de ingresos');

            /**
             * Categoría del rubro según el Marco Fiscal:
             * - corriente:        Ingresos tributarios y no tributarios recurrentes
             * - capital:          Transferencias de capital, créditos, cofinanciación
             * - fondos_especiales: Recursos de destinación específica (estampillas, etc.)
             */
            $table->string('category')->default('corriente');

            $table->decimal('projected_amount', 15, 2)
                ->comment('Meta de recaudo proyectada para la vigencia');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'fiscal_year', 'code'], 'budget_revenues_unique');
        });

        // Columna FK en income_records para vincular recaudos reales al rubro de ingresos
        Schema::table('income_records', function (Blueprint $table) {
            $table->foreignId('budget_revenue_id')
                ->nullable()
                ->after('company_id')
                ->constrained('budget_revenues')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('income_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('budget_revenue_id');
        });

        Schema::dropIfExists('budget_revenues');
    }
};
