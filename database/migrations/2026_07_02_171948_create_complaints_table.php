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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('service_area_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description');
            $table->dateTime('preferred_visit_time')->nullable();

            $table->string('status')->default('new');
            $table->string('priority')->default('medium');

            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
