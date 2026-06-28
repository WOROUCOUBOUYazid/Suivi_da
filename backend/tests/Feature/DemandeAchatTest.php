<?php

namespace Tests\Feature;

use App\Models\DemandeAchat;
use App\Models\Statut;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DemandeAchatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@admin.com')->first();
    }

    private function utilisateur(): User
    {
        return User::where('email', 'user@suivida.com')->first();
    }

    private function payloadDa(array $extra = []): array
    {
        return array_merge([
            'numero_da' => 'DA_0000001',
            'designation' => 'Achat ordinateurs',
            'affectation' => 'Service IT',
            'problematique' => 'Postes obsolètes',
            'apport_solution' => 'Renouvellement du parc',
            'quantite' => 5,
            'date_creation_reelle' => '2026-06-01',
        ], $extra);
    }

    public function test_utilisateur_peut_creer_une_da(): void
    {
        Sanctum::actingAs($this->utilisateur());

        $response = $this->postJson('/api/demandes-achats', $this->payloadDa());

        $response->assertCreated()
            ->assertJsonPath('data.numero_da', 'DA_0000001');

        $this->assertDatabaseHas('demandes_achats', ['numero_da' => 'DA_0000001']);
        // Historique initial + relance planifiée
        $this->assertDatabaseHas('historique_statuts', ['commentaire' => 'Création de la demande']);
        $this->assertDatabaseHas('relances', ['numero_relance' => 1, 'envoyee' => false]);
    }

    public function test_format_numero_da_invalide_est_rejete(): void
    {
        Sanctum::actingAs($this->utilisateur());

        $this->postJson('/api/demandes-achats', $this->payloadDa(['numero_da' => 'DA-123']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('numero_da');
    }

    public function test_numero_da_doit_etre_unique(): void
    {
        Sanctum::actingAs($this->utilisateur());
        $this->postJson('/api/demandes-achats', $this->payloadDa())->assertCreated();

        $this->postJson('/api/demandes-achats', $this->payloadDa(['designation' => 'Autre']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('numero_da');
    }

    public function test_utilisateur_ne_voit_que_ses_propres_da(): void
    {
        $autre = User::create([
            'nom' => 'X', 'prenom' => 'Y', 'email' => 'autre@suivida.com',
            'password' => bcrypt('secret123'), 'poste' => 'P', 'type_connexion' => 'sql', 'actif' => true,
        ]);
        $autre->assignRole('Utilisateur');

        $statut = Statut::orderBy('ordre')->first();
        DemandeAchat::create($this->payloadDa(['numero_da' => 'DA_0000009', 'created_by' => $autre->id, 'statut_id' => $statut->id, 'date_creation_application' => now()]));

        Sanctum::actingAs($this->utilisateur());
        $this->postJson('/api/demandes-achats', $this->payloadDa(['numero_da' => 'DA_0000010']))->assertCreated();

        $response = $this->getJson('/api/demandes-achats');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('DA_0000010', $response->json('data.0.numero_da'));
    }

    public function test_admin_voit_toutes_les_da(): void
    {
        Sanctum::actingAs($this->utilisateur());
        $this->postJson('/api/demandes-achats', $this->payloadDa(['numero_da' => 'DA_0000011']))->assertCreated();

        Sanctum::actingAs($this->admin());
        $this->postJson('/api/demandes-achats', $this->payloadDa(['numero_da' => 'DA_0000012']))->assertCreated();

        $response = $this->getJson('/api/demandes-achats');
        $this->assertCount(2, $response->json('data'));
    }

    public function test_utilisateur_standard_ne_peut_pas_reculer_de_statut(): void
    {
        Sanctum::actingAs($this->utilisateur());
        $create = $this->postJson('/api/demandes-achats', $this->payloadDa())->assertCreated();
        $daId = $create->json('data.id');

        $ordre3 = Statut::where('ordre', 3)->first();
        $ordre1 = Statut::where('ordre', 1)->first();

        // Avance autorisée 1 -> 3
        $this->postJson("/api/demandes-achats/{$daId}/statut", ['statut_id' => $ordre3->id])->assertOk();

        // Retour 3 -> 1 interdit pour un standard
        $this->postJson("/api/demandes-achats/{$daId}/statut", ['statut_id' => $ordre1->id])
            ->assertStatus(422);
    }

    public function test_administrateur_peut_revenir_a_un_statut_anterieur(): void
    {
        // DA créée par l'utilisateur standard
        Sanctum::actingAs($this->utilisateur());
        $daId = $this->postJson('/api/demandes-achats', $this->payloadDa())->json('data.id');
        $ordre3 = Statut::where('ordre', 3)->first();
        $this->postJson("/api/demandes-achats/{$daId}/statut", ['statut_id' => $ordre3->id])->assertOk();

        // L'admin la fait reculer
        Sanctum::actingAs($this->admin());
        $ordre1 = Statut::where('ordre', 1)->first();
        $this->postJson("/api/demandes-achats/{$daId}/statut", ['statut_id' => $ordre1->id])
            ->assertOk()
            ->assertJsonPath('data.statut.ordre', 1);
    }

    public function test_cloture_possible_a_tout_moment_et_renseigne_la_date(): void
    {
        Sanctum::actingAs($this->utilisateur());
        $daId = $this->postJson('/api/demandes-achats', $this->payloadDa())->json('data.id');

        $this->postJson("/api/demandes-achats/{$daId}/cloturer", ['commentaire' => 'DA déjà terminée'])
            ->assertOk()
            ->assertJsonPath('data.statut.est_cloture', true);

        $this->assertNotNull(DemandeAchat::find($daId)->date_cloture);
    }

    public function test_changement_de_statut_reinitialise_la_relance(): void
    {
        Sanctum::actingAs($this->utilisateur());
        $daId = $this->postJson('/api/demandes-achats', $this->payloadDa())->json('data.id');

        $ordre2 = Statut::where('ordre', 2)->first();
        $this->postJson("/api/demandes-achats/{$daId}/statut", ['statut_id' => $ordre2->id])->assertOk();

        // Une seule relance non envoyée active pour le nouveau statut.
        $relancesActives = DemandeAchat::find($daId)->relances()->where('envoyee', false)->get();
        $this->assertCount(1, $relancesActives);
        $this->assertSame($ordre2->id, $relancesActives->first()->statut_id);
    }
}
