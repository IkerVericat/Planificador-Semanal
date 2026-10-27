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
        // citext: email sin distinguir mayúsculas (Ana@x.com = ana@x.com)
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('timezone')->default('Europe/Madrid');
            $table->smallInteger('week_starts_on')->default(1);
            $table->string('default_view')->default('week');
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE users ALTER COLUMN email TYPE citext');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_name_not_blank CHECK (length(trim(name)) > 0)');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_week_starts_on_check CHECK (week_starts_on BETWEEN 0 AND 6)');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_default_view_check CHECK (default_view IN ('week', 'month', 'year'))");

        // Tabla del Password broker de Laravel (el token se guarda hasheado)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
