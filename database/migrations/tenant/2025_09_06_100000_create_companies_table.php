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

            $table->unsignedBigInteger('identity_id')->nullable();
            $table->index('identity_id');
            $table->string('document_number')->nullable()->unique();

            $table->string('address')->nullable();
            // Ubigeo: solo almacenamos el id de distrito
            $table->string('district_id', 6)->nullable();
            $table->index('district_id');
            $table->text('policies')->nullable();
            $table->string('slogan')->nullable();

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
