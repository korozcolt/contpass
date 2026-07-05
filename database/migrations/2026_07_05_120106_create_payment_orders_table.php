<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_obligation_id')->constrained('budget_obligations')->cascadeOnDelete();
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->restrictOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->string('status');
            $table->decimal('amount', 18, 2);
            $table->string('method');
            $table->string('reference')->nullable();
            $table->date('issued_on');
            $table->date('paid_on')->nullable();
            $table->string('description');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'budget_obligation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
