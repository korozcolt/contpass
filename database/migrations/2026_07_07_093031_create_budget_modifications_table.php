<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_modifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            /**
             * Tipo de modificación presupuestal:
             * - addition:  Adición (inyección de nuevos recursos al rubro)
             * - reduction: Reducción (disminución del rubro)
             * - transfer:  Traslado (de un rubro a otro, sin alterar el techo global)
             */
            $table->string('type');

            /**
             * Referencia legal del acto administrativo que respalda la modificación.
             * Ej: "Decreto Municipal 014 de 2026" / "Acta de Junta Directiva N° 45"
             */
            $table->string('document_reference');

            /**
             * Rubro de origen (solo aplica en traslados).
             * Es el rubro al cual se le reduce el saldo.
             */
            $table->foreignId('source_appropriation_id')
                ->nullable()
                ->constrained('budget_appropriations')
                ->nullOnDelete();

            /**
             * Rubro destino.
             * - En adiciones/reducciones: el rubro afectado.
             * - En traslados: el rubro que recibe el saldo.
             */
            $table->foreignId('destination_appropriation_id')
                ->constrained('budget_appropriations')
                ->restrictOnDelete();

            $table->decimal('amount', 15, 2);

            $table->text('justification')->nullable();

            /** Fecha en que la modificación entra en vigencia según el acto administrativo. */
            $table->date('effective_date');

            /** Usuario que registró la modificación en el sistema. */
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_modifications');
    }
};
