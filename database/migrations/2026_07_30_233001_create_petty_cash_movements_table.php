<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_cash_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('petty_cash_fund_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('third_party_id')
                ->nullable()
                ->constrained('third_parties')
                ->nullOnDelete();

            $table->string('type')
                ->comment('opening|expense|replenishment|closure');

            $table->date('date');

            $table->decimal('amount', 15, 2);

            $table->string('support_number')->nullable();

            $table->string('description')->nullable();

            $table->timestamps();

            $table->index(['petty_cash_fund_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_movements');
    }
};
