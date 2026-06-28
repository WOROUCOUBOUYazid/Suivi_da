<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_achats', function (Blueprint $table) {
            $table->id();
            $table->string('numero_da')->unique();
            $table->string('designation');
            $table->string('affectation');
            $table->text('problematique');
            $table->text('apport_solution');
            $table->decimal('quantite', 10, 2);
            $table->text('existant')->nullable();
            $table->foreignId('statut_id')->constrained('statuts');
            $table->date('date_creation_reelle');
            $table->date('date_creation_application');
            $table->date('date_cloture')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->date('date_estimee_action')->nullable();
            $table->integer('delai_personnalise_relance_jours')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_achats');
    }
};
