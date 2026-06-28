<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_historique', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destinataire_id')->constrained('users');
            $table->string('type');
            $table->text('sujet');
            $table->text('contenu');
            $table->string('canal')->default('email');
            $table->boolean('envoyee')->default(false);
            $table->timestamp('date_envoi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_historique');
    }
};
