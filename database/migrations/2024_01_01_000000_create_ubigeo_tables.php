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
        // Departamentos
        Schema::create('ubigeo_departments', function (Blueprint $table) {
            $table->string('id', 2)->primary();
            $table->string('name');
        });

        // Provincias
        Schema::create('ubigeo_provinces', function (Blueprint $table) {
            $table->string('id', 4)->primary();
            $table->string('name');
            $table->string('department_id', 2);

            $table->foreign('department_id')
                ->references('id')
                ->on('ubigeo_departments')
                ->onDelete('cascade');

            $table->index(['department_id']);
        });

        // Distritos
        Schema::create('ubigeo_districts', function (Blueprint $table) {
            $table->string('id', 6)->primary();
            $table->string('name');
            $table->string('province_id', 4);
            $table->string('department_id', 2);

            $table->foreign('province_id')
                ->references('id')
                ->on('ubigeo_provinces')
                ->onDelete('cascade');

            $table->foreign('department_id')
                ->references('id')
                ->on('ubigeo_departments')
                ->onDelete('cascade');

            $table->index(['province_id']);
            $table->index(['department_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ubigeo_districts');
        Schema::dropIfExists('ubigeo_provinces');
        Schema::dropIfExists('ubigeo_departments');
    }
};
