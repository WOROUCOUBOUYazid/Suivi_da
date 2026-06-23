<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_achat_id')->constrained('demandes_achats')->cascadeOnDelete();
            $table->foreignId('statut_id')->constrained('statuts');
            $table->date('date_relance_prevue');
            $table->date('date_relance_envoyee')->nullable();
            $table->boolean('envoyee')->default(false);
            $table->integer('numero_relance')->default(1);
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relances');
    }
};
