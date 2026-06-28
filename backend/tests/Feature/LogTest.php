<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogTest extends TestCase
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

    public function test_admin_peut_consulter_les_logs(): void
    {
        $this->creerDa($this->utilisateur(), 'DA_0000001');

        Sanctum::actingAs($this->admin());
        $response = $this->getJson('/api/logs')->assertOk();

        // Au moins le log de création de DA est présent.
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_filtre_par_action(): void
    {
        $this->creerDa($this->utilisateur(), 'DA_0000002');

        Sanctum::actingAs($this->admin());
        $response = $this->getJson('/api/logs?action=da_creation')->assertOk();

        foreach ($response->json('data') as $log) {
            $this->assertSame('da_creation', $log['action']);
        }
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_utilisateur_standard_ne_peut_pas_voir_les_logs(): void
    {
        Sanctum::actingAs($this->utilisateur());
        $this->getJson('/api/logs')->assertStatus(403);
    }

    public function test_export_csv(): void
    {
        $this->creerDa($this->utilisateur(), 'DA_0000003');

        Sanctum::actingAs($this->admin());
        $response = $this->get('/api/logs/export')->assertOk();

        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }
}
