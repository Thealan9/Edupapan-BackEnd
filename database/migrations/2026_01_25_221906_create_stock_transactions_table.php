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
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained();// libro de donde se guiara para elegir paquetes
            $table->foreignId('user_id')->constrained();//usuario que realizo esto se por venta u orden del admin
            $table->foreignId('ticket_id')->nullable()->constrained();//detalles del proceso
            $table->integer('quantity');//cantidad de paquetes a retirar
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
