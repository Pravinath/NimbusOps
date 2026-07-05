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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('technician_assignment_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('technician_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('scheduled_visit_time')->nullable();
            $table->string('required_skill')->nullable();
            $table->json('suggested_spare_parts')->nullable();

            $table->string('status')->default('created');
            $table->text('visit_notes')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->json('before_photo_metadata')->nullable();
            $table->json('after_photo_metadata')->nullable();

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('on_the_way_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['technician_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
