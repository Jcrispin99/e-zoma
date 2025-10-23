<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('loyalty_program_pos_config')) {
            return;
        }
        Schema::create('loyalty_program_pos_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->foreignId('pos_config_id')->constrained('pos_configs')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['loyalty_program_id', 'pos_config_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_program_pos_config');
    }
};
