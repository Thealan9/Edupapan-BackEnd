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
        Schema::create('ticket_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained();
            $table->enum('status', ['pending', 'completed', 'damaged', 'missing', 'other', 'cancelled'])->default('pending');
            $table->string('moved_to_pallet')->nullable(); //solo para casos de mover paquetes de ubi
            $table->text('description')->nullable(); // solo si esta el paquete en damaged,missing,other
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_details');
    }
};
