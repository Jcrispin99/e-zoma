<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('loyalty_rules')) {
            return;
        }

        Schema::table('loyalty_rules', function (Blueprint $table) {
            // website_id faltante
            if (!Schema::hasColumn('loyalty_rules', 'website_id')) {
                $table->unsignedBigInteger('website_id')->nullable()->after('promo_barcode');
            }
            // Campos para definir cómo se acumulan puntos por regla
            if (!Schema::hasColumn('loyalty_rules', 'amount_per_point')) {
                // Monto de dinero requerido para obtener 1 punto (p. ej., S/10 -> 1 punto)
                $table->decimal('amount_per_point', 12, 2)->nullable()->after('reward_point_mode');
            }
            if (!Schema::hasColumn('loyalty_rules', 'points_per_order')) {
                // Puntos fijos por pedido cuando reward_point_mode = 'order'
                $table->unsignedInteger('points_per_order')->nullable()->after('amount_per_point');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('loyalty_rules')) {
            return;
        }

        Schema::table('loyalty_rules', function (Blueprint $table) {
            if (Schema::hasColumn('loyalty_rules', 'website_id')) {
                $table->dropColumn('website_id');
            }
            if (Schema::hasColumn('loyalty_rules', 'amount_per_point')) {
                $table->dropColumn('amount_per_point');
            }
            if (Schema::hasColumn('loyalty_rules', 'points_per_order')) {
                $table->dropColumn('points_per_order');
            }
        });
    }
};
