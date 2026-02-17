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
            $table->enum('type', ['entry','sale','change','removed','damaged','partial_damaged']);
            $table->enum('status', ['pending', 'in_progress','in_delivery', 'completed','pending_partially_completed','approve_partially','refused_partially','partially_completed', 'cancelled','return'])->default('pending');
            $table->foreignId('assigned_to')->constrained('users');
            $table->integer('quantity'); // cantidad a sacar o meter cambiar etc nunca 0
            $table->text('description')->nullable(); //opcional un detalle
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
