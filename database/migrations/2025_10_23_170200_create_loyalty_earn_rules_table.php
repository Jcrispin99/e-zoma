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
        Schema::create('loyalty_earn_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->onDelete('cascade');
            $table->string('name');
            $table->string('basis'); // 'per_amount' | 'per_unit'
            $table->decimal('points_per_sol', 12, 4)->nullable();
            $table->integer('points_per_unit')->nullable();
            $table->unsignedInteger('min_qty')->nullable();
            $table->decimal('min_amount', 12, 2)->nullable();
            $table->string('scope_type'); // 'all' | 'products' | 'category' | 'variant'
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(10);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_earn_rules');
    }
};
