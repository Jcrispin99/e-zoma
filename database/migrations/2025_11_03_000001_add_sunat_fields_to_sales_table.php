<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'sunat_status')) {
                $table->string('sunat_status')->default('pending')->index();
            }
            if (!Schema::hasColumn('sales', 'sunat_response')) {
                // Usar JSON si el motor lo soporta
                $table->json('sunat_response')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'sunat_status')) {
                $table->dropColumn('sunat_status');
            }
            if (Schema::hasColumn('sales', 'sunat_response')) {
                $table->dropColumn('sunat_response');
            }
        });
    }
};