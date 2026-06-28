<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function creerUtilisateur(array $attributs = []): User
    {
        return User::create(array_merge([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john@example.com',
            'password' => Hash::make('secret123'),
            'poste' => 'Employé',
            'type_connexion' => 'sql',
            'actif' => true,
        ], $attributs));
    }

    public function test_connexion_sql_reussie_retourne_un_token(): void
    {
        $this->creerUtilisateur();

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'roles', 'permissions']]);

        $this->assertNotNull(User::first()->date_derniere_connexion);
    }

    public function test_connexion_echoue_avec_mauvais_mot_de_passe(): void
    {
        $this->creerUtilisateur();

        $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'mauvais',
        ])->assertStatus(422);
    }

    public function test_connexion_refusee_pour_compte_inactif(): void
    {
        $this->creerUtilisateur(['actif' => false]);

        $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'secret123',
        ])->assertStatus(422);
    }

    public function test_route_me_exige_authentification(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_et_logout_avec_token(): void
    {
        $user = $this->creerUtilisateur();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'john@example.com');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
