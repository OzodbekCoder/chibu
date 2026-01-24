<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_expenses', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('shipment_id');
            $table->string('title'); // bojxona, qadoq, sklad, dostavka...
            $table->decimal('amount', 14, 4)->default(0);
            $table->string('currency', 3)->default('USD'); // USD|UZS
            $table->decimal('usd_rate', 14, 4)->nullable(); // agar currency=UZS bo'lsa konvert uchun

            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by_telegram_id')->nullable();

            $table->timestamps();

            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('cascade');
            $table->index(['shipment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_expenses');
    }
};
