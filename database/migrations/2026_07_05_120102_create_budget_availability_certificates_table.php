<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_availability_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_appropriation_id')->constrained('budget_appropriations')->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('status');
            $table->unsignedSmallInteger('fiscal_year');
            $table->decimal('amount', 18, 2);
            $table->text('justification');
            $table->date('issued_on');
            $table->date('expires_on')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'fiscal_year']);
            $table->index(['company_id', 'budget_appropriation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_availability_certificates');
    }
};
