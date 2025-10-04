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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('companies')->onDelete('cascade');

            $table->string('name');
            $table->string('trade_name')->nullable();

            $table->foreignId('identity_id')->constrained('identities')->onDelete('cascade');
            $table->string('document_number')->unique();

            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('tax_address')->nullable();
            $table->string('legal_representative')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
