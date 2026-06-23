<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statuts', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('slug')->unique();
            $table->integer('ordre')->unique();
            $table->string('couleur')->nullable();
            $table->text('description')->nullable();
            $table->boolean('est_cloture')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statuts');
    }
};
