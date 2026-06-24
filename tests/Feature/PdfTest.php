<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
    }

    private function payload(): array
    {
        return [
            'numero_da' => 'DA_0000001', 'designation' => 'PC portables', 'affectation' => 'IT',
            'problematique' => 'Postes en panne', 'apport_solution' => 'Achat neuf', 'quantite' => 3,
            'date_creation_reelle' => '2026-06-01',
        ];
    }

    public function test_le_pdf_est_genere_a_la_creation(): void
    {
        Sanctum::actingAs(User::where('email', 'user@suivida.com')->first());

        $this->postJson('/api/demandes-achats', $this->payload())->assertCreated();

        Storage::disk('local')->assertExists('da-pdf/DA_0000001.pdf');
    }

    public function test_le_pdf_est_telechargeable(): void
    {
        Sanctum::actingAs(User::where('email', 'user@suivida.com')->first());
        $daId = $this->postJson('/api/demandes-achats', $this->payload())->json('data.id');

        $response = $this->get("/api/demandes-achats/{$daId}/pdf");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }
}
