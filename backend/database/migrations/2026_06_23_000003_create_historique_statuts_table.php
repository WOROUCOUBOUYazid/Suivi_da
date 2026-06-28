<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_statuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_achat_id')->constrained('demandes_achats')->cascadeOnDelete();
            $table->foreignId('ancien_statut_id')->nullable()->constrained('statuts');
            $table->foreignId('nouveau_statut_id')->constrained('statuts');
            $table->text('commentaire')->nullable();
            $table->foreignId('utilisateur_id')->constrained('users');
            $table->timestamp('date_changement');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_statuts');
    }
};
