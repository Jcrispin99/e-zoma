<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('loyalty_reward_variant')) {
            return;
        }
        Schema::create('loyalty_reward_variant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_reward_id')->constrained('loyalty_rewards')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('variants')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['loyalty_reward_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_reward_variant');
    }
};
