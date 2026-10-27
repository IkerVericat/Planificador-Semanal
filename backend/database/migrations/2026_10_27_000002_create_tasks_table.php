<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            // Sin FK simple: la FK compuesta de abajo obliga a que la categoría sea del mismo usuario
            $table->uuid('category_id')->nullable()->index();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->date('task_date');
            // NULL = tarea de todo el día
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestampTz('completed_at')->nullable();
            $table->string('recurrence')->default('none');
            $table->date('recurrence_until')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'task_date', 'start_time']);
        });

        // Para 'weekly': 1 = lunes … 7 = domingo
        DB::statement('ALTER TABLE tasks ADD COLUMN recurrence_days smallint[]');

        DB::statement('ALTER TABLE tasks ADD CONSTRAINT tasks_category_same_user
            FOREIGN KEY (user_id, category_id) REFERENCES categories (user_id, id)
            ON DELETE SET NULL (category_id)');

        DB::statement('ALTER TABLE tasks ADD CONSTRAINT tasks_title_not_blank CHECK (length(trim(title)) > 0)');
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_recurrence_check
            CHECK (recurrence IN ('none', 'daily', 'weekly', 'monthly', 'yearly'))");
        DB::statement('ALTER TABLE tasks ADD CONSTRAINT tasks_time_range_check
            CHECK (end_time IS NULL OR (start_time IS NOT NULL AND end_time > start_time))');
        DB::statement('ALTER TABLE tasks ADD CONSTRAINT tasks_recurrence_until_check
            CHECK (recurrence_until IS NULL OR recurrence_until >= task_date)');
        DB::statement('ALTER TABLE tasks ADD CONSTRAINT tasks_recurrence_days_check
            CHECK (recurrence_days IS NULL OR recurrence_days <@ ARRAY[1,2,3,4,5,6,7]::smallint[])');

        DB::statement("CREATE INDEX tasks_user_recurring_index ON tasks (user_id) WHERE recurrence <> 'none'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
