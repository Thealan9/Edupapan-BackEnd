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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['entry','sale','change','removed']);
            $table->enum('status', ['pending', 'in_progress','in_delivery', 'completed','pending_partially_completed','partially_completed', 'cancelled'])->default('pending');
            $table->foreignId('assigned_to')->constrained('users');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles');
            $table->text('details')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
