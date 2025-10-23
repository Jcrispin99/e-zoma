<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('loyalty_rewards')) {
            return;
        }

        Schema::table('loyalty_rewards', function (Blueprint $table) {
            // Tipo de recompensa
            if (!Schema::hasColumn('loyalty_rewards', 'reward_type')) {
                $table->string('reward_type')->default('discount'); // discount | product
            }

            // Modo y aplicabilidad del descuento
            if (!Schema::hasColumn('loyalty_rewards', 'discount_mode')) {
                $table->string('discount_mode')->nullable(); // percent | per_order | per_point
            }
            if (!Schema::hasColumn('loyalty_rewards', 'discount_applicability')) {
                $table->string('discount_applicability')->nullable(); // order | cheapest | specific
            }

            // Valores del descuento
            if (!Schema::hasColumn('loyalty_rewards', 'discount')) {
                $table->decimal('discount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('loyalty_rewards', 'discount_max_amount')) {
                $table->decimal('discount_max_amount', 12, 2)->nullable();
            }

            // Producto de regalo
            if (!Schema::hasColumn('loyalty_rewards', 'reward_product_id')) {
                $table->foreignId('reward_product_id')->nullable()->constrained('variants');
            }
            if (!Schema::hasColumn('loyalty_rewards', 'reward_product_qty')) {
                $table->unsignedInteger('reward_product_qty')->nullable();
            }

            // Requisitos y efectos wallet
            if (!Schema::hasColumn('loyalty_rewards', 'required_points')) {
                $table->decimal('required_points', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('loyalty_rewards', 'clear_wallet')) {
                $table->boolean('clear_wallet')->default(false);
            }

            // Descripción y estado
            if (!Schema::hasColumn('loyalty_rewards', 'description')) {
                $table->json('description')->nullable();
            }
            if (!Schema::hasColumn('loyalty_rewards', 'active')) {
                $table->boolean('active')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('loyalty_rewards')) {
            return;
        }

        Schema::table('loyalty_rewards', function (Blueprint $table) {
            // Al revertir, eliminamos sólo las columnas añadidas por esta migración si existen
            if (Schema::hasColumn('loyalty_rewards', 'reward_type')) {
                $table->dropColumn('reward_type');
            }
            if (Schema::hasColumn('loyalty_rewards', 'discount_mode')) {
                $table->dropColumn('discount_mode');
            }
            if (Schema::hasColumn('loyalty_rewards', 'discount_applicability')) {
                $table->dropColumn('discount_applicability');
            }
            if (Schema::hasColumn('loyalty_rewards', 'discount')) {
                $table->dropColumn('discount');
            }
            if (Schema::hasColumn('loyalty_rewards', 'discount_max_amount')) {
                $table->dropColumn('discount_max_amount');
            }
            if (Schema::hasColumn('loyalty_rewards', 'reward_product_id')) {
                $table->dropConstrainedForeignId('reward_product_id');
            }
            if (Schema::hasColumn('loyalty_rewards', 'reward_product_qty')) {
                $table->dropColumn('reward_product_qty');
            }
            if (Schema::hasColumn('loyalty_rewards', 'required_points')) {
                $table->dropColumn('required_points');
            }
            if (Schema::hasColumn('loyalty_rewards', 'clear_wallet')) {
                $table->dropColumn('clear_wallet');
            }
            if (Schema::hasColumn('loyalty_rewards', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('loyalty_rewards', 'active')) {
                $table->dropColumn('active');
            }
        });
    }
};
