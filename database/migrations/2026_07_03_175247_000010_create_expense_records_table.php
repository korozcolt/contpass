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
        Schema::create('expense_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_account_id')->constrained('chart_accounts')->restrictOnDelete();
            $table->foreignId('payable_account_id')->constrained('chart_accounts')->restrictOnDelete();
            $table->string('support_type');
            $table->string('support_number');
            $table->date('accrual_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('withholding_amount', 15, 2)->default(0);
            $table->boolean('has_valid_support')->default(false);
            $table->boolean('is_deductible')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_records');
    }
};
