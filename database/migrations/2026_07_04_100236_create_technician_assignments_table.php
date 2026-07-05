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
        Schema::create('technician_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('technician_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('assigned_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('status')->default('active');
            $table->boolean('is_override')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->timestamps();

            $table->index(['complaint_id', 'status']);
            $table->index(['technician_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_assignments');
    }
};
