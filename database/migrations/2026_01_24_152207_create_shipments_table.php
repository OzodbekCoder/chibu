<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('track_code', 120)->unique();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            $table->string('vendor_name', 200)->nullable();
            $table->string('order_url')->nullable();

            $table->string('tariff_type', 5)->default('kg'); // kg | piece | m3
            $table->decimal('weight_kg', 12, 3)->nullable();
            $table->decimal('volume_m3', 12, 4)->nullable();
            $table->unsignedInteger('pieces')->nullable();

            $table->decimal('tariff_value', 12, 2)->nullable();
            $table->string('tariff_currency', 3)->nullable();
            $table->decimal('usd_rate', 12, 2)->nullable();
            $table->decimal('price_yuan', 14, 2)->nullable();

            $table->string('delivery_type', 10)->default('avia'); // avia | avto | sea | other

            $table->string('status', 30)->default('CREATED');
            $table->timestamp('status_at')->nullable();
            $table->timestamp('arrived_at')->nullable();

            $table->text('note')->nullable();
            $table->string('ipost_id')->nullable();

            $table->foreignId('created_by_id')
                ->references('id')->on('telegraph_chats')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
