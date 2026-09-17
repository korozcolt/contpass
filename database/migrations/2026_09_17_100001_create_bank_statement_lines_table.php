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
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_import_id')->constrained()->cascadeOnDelete();
            $table->date('line_date')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('reference')->nullable();
            $table->json('raw_row');
            $table->string('status')->default('pending');
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['bank_statement_import_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
