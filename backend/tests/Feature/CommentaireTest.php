<?php

namespace Tests\Feature;

use App\Models\DemandeAchat;
use App\Models\Statut;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentaireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function utilisateur(): User
    {
        return User::where('email', 'user@suivida.com')->first();
    }

    private function creerDaPour(User $user): DemandeAchat
    {
        return DemandeAchat::create([
            'numero_da' => 'DA_0000001',
            'designation' => 'Test', 'affectation' => 'IT',
            'problematique' => 'P', 'apport_solution' => 'S', 'quantite' => 1,
            'statut_id' => Statut::orderBy('ordre')->first()->id,
            'date_creation_reelle' => '2026-06-01', 'date_creation_application' => now(),
            'created_by' => $user->id,
        ]);
    }

    public function test_utilisateur_peut_ajouter_un_commentaire(): void
    {
        $user = $this->utilisateur();
        $da = $this->creerDaPour($user);
        Sanctum::actingAs($user);

        $this->postJson("/api/demandes-achats/{$da->id}/commentaires", ['contenu' => 'Relancé le service achat'])
            ->assertCreated()
            ->assertJsonPath('data.contenu', 'Relancé le service achat');

        $this->assertDatabaseHas('commentaires', ['demande_achat_id' => $da->id, 'contenu' => 'Relancé le service achat']);
    }

    public function test_commentaire_vide_rejete(): void
    {
        $user = $this->utilisateur();
        $da = $this->creerDaPour($user);
        Sanctum::actingAs($user);

        $this->postJson("/api/demandes-achats/{$da->id}/commentaires", ['contenu' => ''])
            ->assertStatus(422);
    }

    public function test_utilisateur_ne_peut_pas_commenter_la_da_d_un_autre(): void
    {
        $proprietaire = $this->utilisateur();
        $da = $this->creerDaPour($proprietaire);

        $autre = User::create([
            'nom' => 'A', 'prenom' => 'B', 'email' => 'b@suivida.com',
            'password' => bcrypt('secret123'), 'poste' => 'P', 'type_connexion' => 'sql', 'actif' => true,
        ]);
        $autre->assignRole('Utilisateur');
        Sanctum::actingAs($autre);

        $this->postJson("/api/demandes-achats/{$da->id}/commentaires", ['contenu' => 'Intrusion'])
            ->assertStatus(403);
    }

    public function test_timeline_historique_liste_les_changements(): void
    {
        $user = $this->utilisateur();
        Sanctum::actingAs($user);
        $daId = $this->postJson('/api/demandes-achats', [
            'numero_da' => 'DA_0000002', 'designation' => 'T', 'affectation' => 'IT',
            'problematique' => 'P', 'apport_solution' => 'S', 'quantite' => 1,
            'date_creation_reelle' => '2026-06-01',
        ])->json('data.id');

        $ordre2 = Statut::where('ordre', 2)->first();
        $this->postJson("/api/demandes-achats/{$daId}/statut", ['statut_id' => $ordre2->id])->assertOk();

        $response = $this->getJson("/api/demandes-achats/{$daId}/historiques")->assertOk();
        // Création + 1 changement = 2 entrées
        $this->assertCount(2, $response->json('data'));
        $this->assertSame('Création de la demande', $response->json('data.0.commentaire'));
    }
}
