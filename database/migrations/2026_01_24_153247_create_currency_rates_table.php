<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('base', 3)->default('USD');   // USD
            $table->string('quote', 3)->default('UZS');  // UZS
            $table->decimal('rate', 14, 4);              // 1 USD = xxxx UZS
            $table->date('rate_date');                   // kurs sanasi

            $table->unsignedBigInteger('created_by_telegram_id')->nullable();

            $table->timestamps();

            $table->unique(['base', 'quote', 'rate_date']);
            $table->index(['rate_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
