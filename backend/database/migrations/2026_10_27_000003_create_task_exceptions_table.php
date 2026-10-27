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
        // Una fila por ocurrencia modificada de una tarea recurrente (saltada o completada ese día)
        Schema::create('task_exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('task_id')->constrained()->cascadeOnDelete();
            $table->date('occurrence_date');
            $table->boolean('is_skipped')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['task_id', 'occurrence_date']);
            $table->index(['user_id', 'occurrence_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_exceptions');
    }
};
