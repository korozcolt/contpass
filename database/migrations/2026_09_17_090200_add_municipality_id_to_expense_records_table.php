<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_records', function (Blueprint $table) {
            $table->foreignId('municipality_id')->nullable()->after('payable_account_id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expense_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('municipality_id');
        });
    }
};
