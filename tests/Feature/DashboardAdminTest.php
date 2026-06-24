<?php

namespace Tests\Feature;

use App\Models\Statut;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardAdminTest extends TestCase
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

    private function creerDa(User $user, string $numero): void
    {
        Sanctum::actingAs($user);
        $this->postJson('/api/demandes-achats', [
            'numero_da' => $numero, 'designation' => 'T', 'affectation' => 'IT',
            'problematique' => 'P', 'apport_solution' => 'S', 'quantite' => 1,
            'date_creation_reelle' => '2026-06-01',
        ])->assertCreated();
    }

    public function test_dashboard_standard_ne_compte_que_ses_da(): void
    {
        $this->creerDa($this->utilisateur(), 'DA_0000001');
        $this->creerDa($this->admin(), 'DA_0000002');

        Sanctum::actingAs($this->utilisateur());
        $response = $this->getJson('/api/dashboard')->assertOk();

        $this->assertSame(1, $response->json('data.total'));
        $this->assertArrayHasKey('par_statut', $response->json('data'));
        $this->assertArrayHasKey('recentes', $response->json('data'));
    }

    public function test_dashboard_admin_voit_toutes_les_da(): void
    {
        $this->creerDa($this->utilisateur(), 'DA_0000003');
        $this->creerDa($this->admin(), 'DA_0000004');

        Sanctum::actingAs($this->admin());
        $response = $this->getJson('/api/dashboard')->assertOk();

        $this->assertSame(2, $response->json('data.total'));
    }

    public function test_admin_peut_creer_un_utilisateur(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/users', [
            'nom' => 'Martin', 'prenom' => 'Paul', 'email' => 'paul@suivida.com',
            'poste' => 'Acheteur', 'type_connexion' => 'sql', 'password' => 'motdepasse',
            'actif' => true, 'role' => 'Utilisateur',
        ])->assertCreated()->assertJsonPath('data.email', 'paul@suivida.com');

        $this->assertTrue(User::where('email', 'paul@suivida.com')->first()->hasRole('Utilisateur'));
    }

    public function test_standard_ne_peut_pas_gerer_les_utilisateurs(): void
    {
        Sanctum::actingAs($this->utilisateur());
        $this->getJson('/api/users')->assertStatus(403);
    }

    public function test_admin_modifie_la_configuration_de_relance(): void
    {
        Sanctum::actingAs($this->admin());
        $statut = Statut::where('slug', 'attente-signature')->first();

        $this->patchJson("/api/configuration-relances/{$statut->id}", [
            'delai_premiere_relance_jours' => 10,
            'delai_relance_suivante_jours' => 4,
            'actif' => true,
        ])->assertOk();

        $this->assertDatabaseHas('configuration_relances', [
            'statut_id' => $statut->id,
            'delai_premiere_relance_jours' => 10,
        ]);
    }

    public function test_admin_met_a_jour_les_parametres(): void
    {
        Sanctum::actingAs($this->admin());

        $this->patchJson('/api/parametres', [
            'parametres' => [
                ['cle' => 'notifications_email_actif', 'valeur' => 'false'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('parametres', [
            'cle' => 'notifications_email_actif',
            'valeur' => 'false',
        ]);
    }
}
