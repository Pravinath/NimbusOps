<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_reference')->unique();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('preferred_service_area_id')->nullable()->constrained('service_areas')->nullOnDelete();
            $table->string('phone', 30);
            $table->string('address');
            $table->string('city', 120);
            $table->unsignedTinyInteger('years_experience')->default(0);
            $table->json('skills');
            $table->text('motivation');
            $table->string('status')->default('submitted')->index();
            $table->timestamp('submitted_at');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_applications');
    }
};
