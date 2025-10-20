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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            $table->string('serie');
            $table->string('correlative');

            $table->foreignId('journal_id')->constrained('journals')->onDelete('cascade');

            $table->timestamp('date')
                ->useCurrent();

            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            $table->decimal('total', 10, 2)->default(0.00);
            $table->string('status')->nullable()->default('draft');

            $table->string('observation')->nullable();

            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->unique(['serie', 'correlative']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
