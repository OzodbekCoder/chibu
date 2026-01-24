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

            $table->string('track_code', 120)->unique(); // TREK / ORDER ID
            $table->string('vendor_name')->nullable();   // Xitoy vendor / ta'minotchi
            $table->string('sender_name')->nullable();   // yuboruvchi (ixtiyoriy)
            $table->string('receiver_name')->nullable(); // qabul qiluvchi (ixtiyoriy)
            $table->string('receiver_phone', 50)->nullable();

            // O'lchovlar
            $table->decimal('weight_kg', 12, 3)->nullable();
            $table->decimal('volume_m3', 12, 4)->nullable();
            $table->unsignedInteger('pieces')->nullable();

            // Tarif
            $table->string('tariff_type', 20)->default('kg'); // kg|m3|piece
            $table->decimal('tariff_value', 14, 4)->default(0); // masalan 3.2 (USD/kg) yoki UZS/kg
            $table->string('tariff_currency', 3)->default('USD'); // USD|UZS
            $table->decimal('usd_rate', 14, 4)->nullable(); // agar UZS ko'rsatmoqchi bo'lsangiz

            // Status
            $table->string('status', 30)->default('CREATED'); // CREATED, CHINA_WAREHOUSE, ON_THE_WAY...
            $table->timestamp('status_at')->nullable();

            // Qo'shimcha
            $table->text('note')->nullable();

            // Audit (kim yaratdi)
            $table->unsignedBigInteger('created_by_chat_id')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['vendor_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
