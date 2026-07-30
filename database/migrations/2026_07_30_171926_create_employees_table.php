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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dependency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pension_fund_id')->nullable()->constrained('payroll_funds')->nullOnDelete();
            $table->foreignId('health_fund_id')->nullable()->constrained('payroll_funds')->nullOnDelete();
            $table->string('tax_id');
            $table->unsignedTinyInteger('verification_digit')->nullable();
            $table->string('name');
            $table->string('position');
            $table->string('contract_type');
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->decimal('base_salary', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'tax_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
