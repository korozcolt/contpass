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
        Schema::table('expense_records', function (Blueprint $table) {
            $table->foreignId('budget_obligation_id')->nullable()->after('voucher_id')->constrained('budget_obligations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_records', function (Blueprint $table) {
            $table->dropForeign(['budget_obligation_id']);
            $table->dropColumn('budget_obligation_id');
        });
    }
};
