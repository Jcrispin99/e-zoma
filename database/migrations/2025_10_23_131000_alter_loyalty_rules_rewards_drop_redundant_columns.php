<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Quitar columnas redundantes en loyalty_rules
        if (Schema::hasTable('loyalty_rules')) {
            Schema::table('loyalty_rules', function (Blueprint $table) {
                if (Schema::hasColumn('loyalty_rules', 'company_id')) {
                    $table->dropConstrainedForeignId('company_id');
                }
                if (Schema::hasColumn('loyalty_rules', 'website_id')) {
                    $table->dropColumn('website_id');
                }
            });
        }

        // Quitar columnas redundantes en loyalty_rewards
        if (Schema::hasTable('loyalty_rewards')) {
            Schema::table('loyalty_rewards', function (Blueprint $table) {
                if (Schema::hasColumn('loyalty_rewards', 'company_id')) {
                    $table->dropConstrainedForeignId('company_id');
                }
            });
        }
    }

    public function down(): void
    {
        // Restaurar columnas si se requiere revertir
        if (Schema::hasTable('loyalty_rules')) {
            Schema::table('loyalty_rules', function (Blueprint $table) {
                if (!Schema::hasColumn('loyalty_rules', 'company_id')) {
                    $table->foreignId('company_id')->nullable()->constrained('companies');
                }
                if (!Schema::hasColumn('loyalty_rules', 'website_id')) {
                    $table->unsignedBigInteger('website_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('loyalty_rewards')) {
            Schema::table('loyalty_rewards', function (Blueprint $table) {
                if (!Schema::hasColumn('loyalty_rewards', 'company_id')) {
                    $table->foreignId('company_id')->nullable()->constrained('companies');
                }
            });
        }
    }
};
