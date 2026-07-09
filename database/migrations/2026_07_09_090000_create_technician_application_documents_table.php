<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_application_id');
            $table->foreign('technician_application_id', 'tech_app_docs_app_id_fk')
                ->references('id')
                ->on('technician_applications')
                ->cascadeOnDelete();
            $table->string('document_type', 60);
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->string('status')->default('uploaded')->index();
            $table->foreignId('reviewed_by_user_id')->nullable();
            $table->foreign('reviewed_by_user_id', 'tech_app_docs_reviewer_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['technician_application_id', 'document_type'], 'tech_app_docs_app_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_application_documents');
    }
};