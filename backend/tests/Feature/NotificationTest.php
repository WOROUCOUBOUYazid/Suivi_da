<?php

namespace Tests\Feature;

use App\Models\Statut;
use App\Models\User;
use App\Notifications\ChangementStatutNotification;
use App\Notifications\DaCreeeNotification;
use App\Services\ParametreService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Notification::fake();
    }

    private function utilisateur(): User
    {
        return User::where('email', 'user@suivida.com')->first();
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'numero_da' => 'DA_0000001', 'designation' => 'T', 'affectation' => 'IT',
            'problematique' => 'P', 'apport_solution' => 'S', 'quantite' => 1,
            'date_creation_reelle' => '2026-06-01',
        ], $extra);
    }

    public function test_creation_da_envoie_et_historise_la_notification(): void
    {
        $user = $this->utilisateur();
        Sanctum::actingAs($user);

        $this->postJson('/api/demandes-achats', $this->payload())->assertCreated();

        Notification::assertSentTo($user, DaCreeeNotification::class);
        $this->assertDatabaseHas('notifications_historique', [
            'destinataire_id' => $user->id,
            'type' => 'da_creee',
            'envoyee' => true,
        ]);
    }

    public function test_changement_statut_notifie_le_createur(): void
    {
        $user = $this->utilisateur();
        Sanctum::actingAs($user);
        $daId = $this->postJson('/api/demandes-achats', $this->payload())->json('data.id');

        $ordre2 = Statut::where('ordre', 2)->first();
        $this->postJson("/api/demandes-achats/{$daId}/statut", ['statut_id' => $ordre2->id])->assertOk();

        Notification::assertSentTo($user, ChangementStatutNotification::class);
    }

    public function test_parametre_desactive_empeche_envoi_mais_historise(): void
    {
        app(ParametreService::class)->set('notifications_email_actif', 'false');

        $user = $this->utilisateur();
        Sanctum::actingAs($user);
        $this->postJson('/api/demandes-achats', $this->payload())->assertCreated();

        Notification::assertNothingSent();
        $this->assertDatabaseHas('notifications_historique', [
            'type' => 'da_creee',
            'envoyee' => false,
        ]);
    }
}
