<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuration_relances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statut_id')->constrained('statuts')->cascadeOnDelete();
            $table->integer('delai_premiere_relance_jours')->default(7);
            $table->integer('delai_relance_suivante_jours')->default(2);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_relances');
    }
};
