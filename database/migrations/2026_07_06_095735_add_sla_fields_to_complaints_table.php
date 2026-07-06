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
        Schema::table('complaints', function (Blueprint $table) {
            $table->timestamp('sla_due_at')
                ->nullable()
                ->after('priority');

            $table->boolean('is_sla_breached')
                ->default(false)
                ->after('sla_due_at');

            $table->timestamp('sla_breached_at')
                ->nullable()
                ->after('is_sla_breached');

            $table->timestamp('sla_escalated_at')
                ->nullable()
                ->after('sla_breached_at');

            $table->index([
                'is_sla_breached',
                'sla_due_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropIndex([
                'is_sla_breached',
                'sla_due_at',
            ]);

            $table->dropColumn([
                'sla_due_at',
                'is_sla_breached',
                'sla_breached_at',
                'sla_escalated_at',
            ]);
        });
    }
};
