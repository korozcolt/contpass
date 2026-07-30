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
        Schema::create('warehouse_movement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_movement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->string('asset_tag')->nullable();
            $table->timestamps();

            $table->index(['warehouse_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_movement_lines');
    }
};
