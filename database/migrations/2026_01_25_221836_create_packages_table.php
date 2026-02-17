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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique()->index();
            $table->foreignId('book_id')->constrained()->onDelete('restrict');
            $table->foreignId('pallet_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('book_quantity');
            $table->enum('status', [
                'pending',     // en proceso para ingresar
                'available',   // Disponible para asignar
                'return',      //devuelto
                'reserved',    // Ya asignado a un ticket, no se puede usar por otro
                'sold',        // Vendido
                'removed',     // sacar del almacen
                'damaged',     // perdida total
                'partial_damaged', // pérdida parcial, retirar por ticket como removed
                'missing',     //perdido
                'other'        //caso unicos
            ])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
