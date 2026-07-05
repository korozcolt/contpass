<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_availability_certificate_id')->constrained('budget_availability_certificates')->cascadeOnDelete();
            $table->foreignId('third_party_id')->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->string('status');
            $table->unsignedSmallInteger('fiscal_year');
            $table->decimal('amount', 18, 2);
            $table->text('justification');
            $table->date('issued_on');
            $table->timestamps();

            $table->index(['company_id', 'status', 'fiscal_year']);
            $table->index(['company_id', 'budget_availability_certificate_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_registrations');
    }
};
