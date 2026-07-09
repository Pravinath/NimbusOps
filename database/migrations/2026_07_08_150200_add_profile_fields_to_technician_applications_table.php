<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technician_applications', function (Blueprint $table) {
            $table->string('full_name', 120)->nullable()->after('user_id');
            $table->date('date_of_birth')->nullable()->after('full_name');
            $table->string('highest_qualification')->nullable()->after('years_experience');
        });
    }

    public function down(): void
    {
        Schema::table('technician_applications', function (Blueprint $table) {
            $table->dropColumn([
                'full_name',
                'date_of_birth',
                'highest_qualification',
            ]);
        });
    }
};
