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
        Schema::create('work_order_spare_parts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('work_order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('spare_part_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('technician_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamp('used_at');
            $table->timestamps();

            $table->index(['work_order_id', 'spare_part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_spare_parts');
    }
};
