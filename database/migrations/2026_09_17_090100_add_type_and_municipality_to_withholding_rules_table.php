<?php

use App\Enums\WithholdingType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withholding_rules', function (Blueprint $table) {
            $table->string('type')->default(WithholdingType::ReteFuente->value)->after('concept');
            $table->string('description')->nullable()->after('type');
            $table->foreignId('municipality_id')->nullable()->after('description')->constrained()->restrictOnDelete();
        });

        DB::statement('UPDATE withholding_rules SET description = concept');

        Schema::table('withholding_rules', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'concept', 'starts_on', 'ends_on']);
            $table->dropColumn('concept');
            $table->index(['company_id', 'type', 'municipality_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::table('withholding_rules', function (Blueprint $table) {
            $table->string('concept')->nullable()->after('chart_account_id');
        });

        DB::statement('UPDATE withholding_rules SET concept = description');

        Schema::table('withholding_rules', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'type', 'municipality_id', 'starts_on', 'ends_on']);
            $table->dropConstrainedForeignId('municipality_id');
            $table->dropColumn(['type', 'description']);
            $table->index(['company_id', 'concept', 'starts_on', 'ends_on']);
        });

        Schema::table('withholding_rules', function (Blueprint $table) {
            $table->string('concept')->nullable(false)->change();
        });
    }
};
