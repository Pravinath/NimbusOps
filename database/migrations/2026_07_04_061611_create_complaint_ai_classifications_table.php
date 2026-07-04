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
        Schema::create('complaint_ai_classifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('provider')->default('mock');
            $table->string('issue_category');
            $table->string('predicted_priority');
            $table->string('suggested_skill');
            $table->json('suggested_spare_parts')->nullable();
            $table->unsignedInteger('suggested_sla_minutes');
            $table->boolean('repeated_complaint_risk')->default(false);
            $table->text('summary');
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('classified_at');
            $table->timestamps();

            $table->index([
                'issue_category',
                'predicted_priority',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaint_ai_classifications');
    }
};
