<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('third_party_id')->constrained()->restrictOnDelete();
            $table->string('number');
            $table->string('status')->default('draft');
            $table->foreignId('revenue_account_id')->constrained('chart_accounts')->restrictOnDelete();
            $table->foreignId('receivable_account_id')->constrained('chart_accounts')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->date('expires_on');
            $table->string('rejection_reason')->nullable();
            $table->foreignId('voucher_id')->nullable()->unique()->constrained('vouchers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
