<?php

namespace Database\Seeders;

use App\Models\ConfigurationRelance;
use App\Models\Parametre;
use App\Models\Statut;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. Création des permissions
        // ==========================================
        $permissions = [
            'view own da',
            'create da',
            'edit da',
            'close da',
            'view all da',
            'manage users',
            'manage settings',
            'manage roles',
            'manage notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // ==========================================
        // 2. Création des rôles et assignation
        // ==========================================
        $roleUtilisateur = Role::create(['name' => 'Utilisateur', 'guard_name' => 'web']);
        $roleUtilisateur->givePermissionTo([
            'view own da',
            'create da',
            'edit da',
            'close da',
        ]);

        $roleAdmin = Role::create(['name' => 'Administrateur', 'guard_name' => 'web']);
        $roleAdmin->givePermissionTo(Permission::all());

        // ==========================================
        // 3. Création des statuts
        // ==========================================
        $statutsData = [
            ['libelle' => 'Attente signature',       'slug' => 'attente-signature',       'ordre' => 1, 'couleur' => '#F59E0B', 'est_cloture' => false],
            ['libelle' => 'Attente de devis',         'slug' => 'attente-devis',           'ordre' => 2, 'couleur' => '#3B82F6', 'est_cloture' => false],
            ['libelle' => 'Validation de devis',      'slug' => 'validation-devis',        'ordre' => 3, 'couleur' => '#8B5CF6', 'est_cloture' => false],
            ['libelle' => 'Attente de commande',      'slug' => 'attente-commande',        'ordre' => 4, 'couleur' => '#EC4899', 'est_cloture' => false],
            ['libelle' => 'Attente de livraison',     'slug' => 'attente-livraison',       'ordre' => 5, 'couleur' => '#14B8A6', 'est_cloture' => false],
            ['libelle' => 'Livré',                   'slug' => 'livre',                   'ordre' => 6, 'couleur' => '#10B981', 'est_cloture' => false],
            ['libelle' => 'Clôturé',                 'slug' => 'cloture',                 'ordre' => 7, 'couleur' => '#6B7280', 'est_cloture' => true],
        ];

        foreach ($statutsData as $data) {
            Statut::create($data);
        }

        // ==========================================
        // 4. Configuration relances par défaut
        // ==========================================
        $configsRelance = [
            ['statut_slug' => 'attente-signature',   'premiere' => 3,  'suivante' => 2,  'actif' => true],
            ['statut_slug' => 'attente-devis',       'premiere' => 5,  'suivante' => 3,  'actif' => true],
            ['statut_slug' => 'validation-devis',    'premiere' => 5,  'suivante' => 3,  'actif' => true],
            ['statut_slug' => 'attente-commande',    'premiere' => 7,  'suivante' => 4,  'actif' => true],
            ['statut_slug' => 'attente-livraison',   'premiere' => 15, 'suivante' => 5,  'actif' => true],
            ['statut_slug' => 'livre',               'premiere' => 7,  'suivante' => 7,  'actif' => false],
            ['statut_slug' => 'cloture',             'premiere' => 0,  'suivante' => 0,  'actif' => false],
        ];

        foreach ($configsRelance as $config) {
            $statut = Statut::where('slug', $config['statut_slug'])->first();
            if ($statut) {
                ConfigurationRelance::create([
                    'statut_id' => $statut->id,
                    'delai_premiere_relance_jours' => $config['premiere'],
                    'delai_relance_suivante_jours' => $config['suivante'],
                    'actif' => $config['actif'],
                ]);
            }
        }

        // ==========================================
        // 5. Paramètres applicatifs
        // ==========================================
        $parametres = [
            ['cle' => 'relance_premiere_delai_defaut', 'valeur' => '7',  'groupe' => 'relances', 'description' => 'Délai avant première relance (jours)'],
            ['cle' => 'relance_suivante_delai_defaut',  'valeur' => '2',  'groupe' => 'relances', 'description' => 'Délai entre relances suivantes (jours)'],
            ['cle' => 'notifications_email_actif',      'valeur' => 'true', 'groupe' => 'notifications', 'description' => 'Activer les notifications email'],
            ['cle' => 'smtp_hote',                      'valeur' => '',    'groupe' => 'smtp', 'description' => 'Serveur SMTP'],
            ['cle' => 'smtp_port',                      'valeur' => '587', 'groupe' => 'smtp', 'description' => 'Port SMTP'],
            ['cle' => 'smtp_username',                  'valeur' => '',    'groupe' => 'smtp', 'description' => 'Utilisateur SMTP'],
            ['cle' => 'smtp_password',                  'valeur' => '',    'groupe' => 'smtp', 'description' => 'Mot de passe SMTP'],
            ['cle' => 'smtp_encryption',                'valeur' => 'tls', 'groupe' => 'smtp', 'description' => 'Chiffrement SMTP (tls/ssl)'],
        ];

        foreach ($parametres as $param) {
            Parametre::create($param);
        }

        // ==========================================
        // 6. Utilisateur admin par défaut
        // ==========================================
        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'Super',
            'email' => 'admin@suivida.com',
            'password' => Hash::make('admin123'),
            'poste' => 'Administrateur système',
            'type_connexion' => 'sql',
            'actif' => true,
        ]);
        $admin->assignRole('Administrateur');

        // ==========================================
        // 7. Utilisateur standard de test
        // ==========================================
        $user = User::create([
            'nom' => 'User',
            'prenom' => 'Test',
            'email' => 'user@suivida.com',
            'password' => Hash::make('user123'),
            'poste' => 'Employé',
            'type_connexion' => 'sql',
            'actif' => true,
        ]);
        $user->assignRole('Utilisateur');
    }
}
