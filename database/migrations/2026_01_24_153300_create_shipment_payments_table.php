<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_payments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('shipment_id')
                ->references('id')->on('shipments')
                ->cascadeOnDelete();

            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('USD'); // USD | UZS
            $table->decimal('usd_rate', 12, 2)->nullable();

            $table->date('paid_at');
            $table->text('note')->nullable();

            $table->foreignId('created_by_id')
                ->references('id')->on('telegraph_chats')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->index(['shipment_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_payments');
    }
};
