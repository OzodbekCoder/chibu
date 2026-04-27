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
            $table->string('order_link')->nullable();   // yuboruvchi (ixtiyoriy)

            // O'lchovlar
            $table->decimal('weight_kg', 12, 3)->nullable();
            $table->decimal('volume_m3', 12, 4)->nullable();
            $table->unsignedInteger('pieces');
            $table->string('client_id', 12)->nullable();

            // Status
            $table->string('status', 30)->default('CREATED'); // CREATED, CHINA_WAREHOUSE, ON_THE_WAY...
            $table->timestamp('status_at')->nullable();

            // Qo'shimcha
            $table->string('tariff_type', 5)->default('kg'); // kg, piece, m3

            // Audit (kim yaratdi)
            $table->foreignId('created_by_id')->references('id')->on('telegraph_chats')->cascadeOnDelete();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
