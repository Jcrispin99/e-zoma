<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter columns to DECIMAL(10,2) using raw SQL to avoid doctrine/dbal dependency
        DB::statement('ALTER TABLE loyalty_accounts MODIFY points_balance DECIMAL(10,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE loyalty_accounts MODIFY points_lifetime DECIMAL(10,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE loyalty_transactions MODIFY points DECIMAL(10,2) NOT NULL');
        DB::statement('ALTER TABLE loyalty_transactions MODIFY available_points DECIMAL(10,2) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to INTEGER
        DB::statement('ALTER TABLE loyalty_accounts MODIFY points_balance INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE loyalty_accounts MODIFY points_lifetime INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE loyalty_transactions MODIFY points INT NOT NULL');
        DB::statement('ALTER TABLE loyalty_transactions MODIFY available_points INT NULL');
    }
};