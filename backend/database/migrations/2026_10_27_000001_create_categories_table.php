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
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->char('color', 7);
            $table->smallInteger('position')->default(0);
            $table->timestampsTz();

            $table->unique(['user_id', 'name']);
            // Permite la FK compuesta (user_id, category_id) desde tasks
            $table->unique(['user_id', 'id']);
        });

        DB::statement('ALTER TABLE categories ADD CONSTRAINT categories_name_not_blank CHECK (length(trim(name)) > 0)');
        DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_color_check CHECK (color ~ '^#[0-9A-Fa-f]{6}$')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
