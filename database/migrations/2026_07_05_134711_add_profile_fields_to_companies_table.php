<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('type', 20)->default('private')->after('currency');
            $table->string('phone', 50)->nullable()->after('type');
            $table->string('email', 150)->nullable()->after('phone');
            $table->string('address', 255)->nullable()->after('email');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('legal_representative', 150)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'phone',
                'email',
                'address',
                'city',
                'legal_representative',
            ]);
        });
    }
};
