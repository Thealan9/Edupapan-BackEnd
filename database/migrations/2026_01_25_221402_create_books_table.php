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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('isbn')->unique();
            $table->enum('level', ['A1','A2', 'B1','B2','C1','C2']);
            $table->decimal('price', 8, 2);
            $table->integer('quantity');
            $table->text('description');
            $table->string('autor');
            $table->boolean('active')->default(false); //solo es para saber si se esta vendiendo o no
            $table->integer('pages');
            $table->integer('year');
            $table->integer('edition');
            $table->enum('format', ['Bolsillo','Tapa Blanda','Tapa Dura']);
            $table->string('size');
            $table->string('supplier'); //editorial
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
