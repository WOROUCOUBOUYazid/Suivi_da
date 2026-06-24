<?php

namespace Tests\Feature;

use App\Models\DemandeAchat;
use App\Models\Relance;
use App\Models\User;
use App\Notifications\RelanceNotification;
use App\Services\RelanceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RelanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Notification::fake();
    }

    private function creerDa(): DemandeAchat
    {
        $user = User::where('email', 'user@suivida.com')->first();
        Sanctum::actingAs($user);
        $id = $this->postJson('/api/demandes-achats', [
            'numero_da' => 'DA_0000001', 'designation' => 'T', 'affectation' => 'IT',
            'problematique' => 'P', 'apport_solution' => 'S', 'quantite' => 1,
            'date_creation_reelle' => '2026-06-01',
        ])->json('data.id');

        return DemandeAchat::find($id);
    }

    public function test_une_relance_est_planifiee_a_la_creation(): void
    {
        $da = $this->creerDa();

        // Statut « attente-signature » : première relance à 3 jours (seeder).
        $relance = $da->relances()->where('envoyee', false)->first();
        $this->assertNotNull($relance);
        $this->assertSame(1, $relance->numero_relance);
        $this->assertTrue($relance->date_relance_prevue->isFuture());
    }

    public function test_relance_due_envoyee_et_planifie_la_suivante(): void
    {
        $da = $this->creerDa();
        $relance = $da->relances()->first();
        // Rendre la relance échue.
        $relance->update(['date_relance_prevue' => now()->subDay()->toDateString()]);

        $nb = app(RelanceService::class)->traiterRelancesDues();

        $this->assertSame(1, $nb);
        Notification::assertSentTo($da->createur, RelanceNotification::class);

        $this->assertTrue($relance->fresh()->envoyee);
        // Une relance suivante (n°2) a été planifiée.
        $suivante = $da->relances()->where('numero_relance', 2)->where('envoyee', false)->first();
        $this->assertNotNull($suivante);
    }

    public function test_relance_non_echue_n_est_pas_envoyee(): void
    {
        $this->creerDa(); // relance à 3 jours dans le futur

        $nb = app(RelanceService::class)->traiterRelancesDues();

        $this->assertSame(0, $nb);
        Notification::assertNotSentTo(User::where('email', 'user@suivida.com')->first(), RelanceNotification::class);
    }

    public function test_da_cloturee_n_a_pas_de_relance(): void
    {
        $da = $this->creerDa();
        $this->postJson("/api/demandes-achats/{$da->id}/cloturer")->assertOk();

        // Après clôture, aucune relance active.
        $this->assertSame(0, $da->relances()->where('envoyee', false)->count());
    }

    public function test_commande_artisan_relances(): void
    {
        $da = $this->creerDa();
        $da->relances()->first()->update(['date_relance_prevue' => now()->subDay()->toDateString()]);

        $this->artisan('relances:envoyer')
            ->expectsOutputToContain('relance(s) envoyée(s).')
            ->assertExitCode(0);
    }
}
