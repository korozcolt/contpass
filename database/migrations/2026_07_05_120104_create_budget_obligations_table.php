<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_registration_id')->constrained('budget_registrations')->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->string('status');
            $table->unsignedSmallInteger('fiscal_year');
            $table->decimal('amount', 18, 2);
            $table->string('support_type');
            $table->string('support_number');
            $table->date('accrual_date');
            $table->string('description')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'budget_registration_id', 'status']);
            $table->index(['company_id', 'status', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_obligations');
    }
};
