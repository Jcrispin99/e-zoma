<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('loyalty_programs')) {
            return;
        }

        Schema::table('loyalty_programs', function (Blueprint $table) {
            if (!Schema::hasColumn('loyalty_programs', 'applies_on')) {
                $table->string('applies_on')->default('current');
            }
            if (!Schema::hasColumn('loyalty_programs', 'trigger')) {
                $table->string('trigger')->nullable();
            }
            if (!Schema::hasColumn('loyalty_programs', 'date_from')) {
                $table->date('date_from')->nullable();
            }
            if (!Schema::hasColumn('loyalty_programs', 'date_to')) {
                $table->date('date_to')->nullable();
            }
            if (!Schema::hasColumn('loyalty_programs', 'active')) {
                $table->boolean('active')->default(true);
            }
            if (!Schema::hasColumn('loyalty_programs', 'sale_ok')) {
                $table->boolean('sale_ok')->default(true);
            }
            if (!Schema::hasColumn('loyalty_programs', 'ecommerce_ok')) {
                $table->boolean('ecommerce_ok')->default(false);
            }
            if (!Schema::hasColumn('loyalty_programs', 'pos_ok')) {
                $table->boolean('pos_ok')->default(false);
            }
            if (!Schema::hasColumn('loyalty_programs', 'limit_usage')) {
                $table->boolean('limit_usage')->nullable();
            }
            if (!Schema::hasColumn('loyalty_programs', 'max_usage')) {
                $table->unsignedInteger('max_usage')->nullable();
            }
            if (!Schema::hasColumn('loyalty_programs', 'website_id')) {
                $table->unsignedBigInteger('website_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('loyalty_programs')) {
            return;
        }

        Schema::table('loyalty_programs', function (Blueprint $table) {
            if (Schema::hasColumn('loyalty_programs', 'website_id')) {
                $table->dropColumn('website_id');
            }
            if (Schema::hasColumn('loyalty_programs', 'max_usage')) {
                $table->dropColumn('max_usage');
            }
            if (Schema::hasColumn('loyalty_programs', 'limit_usage')) {
                $table->dropColumn('limit_usage');
            }
            if (Schema::hasColumn('loyalty_programs', 'pos_ok')) {
                $table->dropColumn('pos_ok');
            }
            if (Schema::hasColumn('loyalty_programs', 'ecommerce_ok')) {
                $table->dropColumn('ecommerce_ok');
            }
            if (Schema::hasColumn('loyalty_programs', 'sale_ok')) {
                $table->dropColumn('sale_ok');
            }
            if (Schema::hasColumn('loyalty_programs', 'active')) {
                $table->dropColumn('active');
            }
            if (Schema::hasColumn('loyalty_programs', 'date_to')) {
                $table->dropColumn('date_to');
            }
            if (Schema::hasColumn('loyalty_programs', 'date_from')) {
                $table->dropColumn('date_from');
            }
            if (Schema::hasColumn('loyalty_programs', 'trigger')) {
                $table->dropColumn('trigger');
            }
            if (Schema::hasColumn('loyalty_programs', 'applies_on')) {
                $table->dropColumn('applies_on');
            }
        });
    }
};
