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
        // Mover una sola ocurrencia de una tarea recurrente a otro día (arrastrar y soltar)
        Schema::table('task_exceptions', function (Blueprint $table) {
            $table->date('moved_to_date')->nullable()->after('occurrence_date');

            $table->index(['user_id', 'moved_to_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_exceptions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'moved_to_date']);
            $table->dropColumn('moved_to_date');
        });
    }
};
