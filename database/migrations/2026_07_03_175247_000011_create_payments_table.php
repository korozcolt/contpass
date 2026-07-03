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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->foreignId('cash_account_id')->constrained()->restrictOnDelete();
            $table->string('method');
            $table->string('reference')->nullable();
            $table->date('paid_on');
            $table->decimal('amount', 15, 2);
            $table->boolean('is_bancarized')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
