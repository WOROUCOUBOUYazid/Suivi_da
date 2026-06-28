# Suivi des Demandes d'Achat (DA)

Application de suivi des Demandes d'Achat. Le projet est désormais découpé en
**deux applications indépendantes** :

```
suivi_da/
├── backend/     → API REST Laravel 12 (SQL Server / SQLite, Sanctum, LDAP, DomPDF)
├── frontend/    → SPA React (Vite 7, React Router, Ant Design, React Query, Axios)
└── suivi_da.md  → Spécifications fonctionnelles
```

Le **backend** expose uniquement une API REST (`/api/*`). Le **frontend** est une
SPA autonome qui consomme cette API. L'authentification se fait par **token Bearer
(Sanctum)** stocké côté client — pas de cookie de session, donc pas de CSRF cookie.

---

## Prérequis

- **PHP ≥ 8.2**, **Composer**
- **SQL Server** (cible) ou SQLite (développement rapide)
- **Node.js ≥ 20** (le frontend utilise Vite 7 / React 19 ; Node 18 ne suffit pas)

> ⚠️ **Réseau d'entreprise (proxy SSL)** : si `npm install` échoue sur une erreur
> TLS (renégociation non sécurisée), utilisez le contournement fourni dans
> `frontend/` (voir la section *Frontend* ci-dessous).

---

## Backend (`backend/`)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Configurer la base (SQL Server ou SQLite) et le SMTP/LDAP dans .env
php artisan migrate --seed
php artisan serve            # http://localhost:8000
```

Dans `.env`, pensez à renseigner :

- **Base de données** : `DB_CONNECTION=sqlsrv` + hôte/port/identifiants (ou `sqlite`).
- **CORS** : `FRONTEND_URL=http://localhost:5173` (origine(s) du frontend, séparées
  par des virgules).
- **Mail / LDAP** selon votre environnement.

Comptes créés par le seeder :

| Rôle           | Email             | Mot de passe |
|----------------|-------------------|--------------|
| Administrateur | `admin@admin.com` | `P@ssw0rd`   |
| Utilisateur    | `user@suivida.com`| `user123`    |

### File d'attente et relances

```bash
php artisan queue:listen      # traitement des notifications (queue)
php artisan schedule:work     # déclenche les relances quotidiennes (08:00)
php artisan relances:envoyer  # déclenchement manuel des relances
```

---

## Frontend (`frontend/`)

```bash
cd frontend
npm install
npm run dev                   # http://localhost:5173
```

L'URL de l'API est lue depuis `frontend/.env` :

```
VITE_API_URL=http://localhost:8000/api
```

### Contournement proxy SSL d'entreprise

Si `npm install` échoue sur une erreur de renégociation TLS, exécutez les commandes
en activant le patch legacy fourni :

```powershell
$env:NODE_OPTIONS = "--require `"$PWD\tls-legacy-patch.cjs`""
$env:OPENSSL_CONF = "$PWD\openssl-legacy.cnf"
npm install
```

(`tls-legacy-patch.cjs` et `openssl-legacy.cnf` sont présents dans `frontend/`.)

### Build de production

```bash
npm run build                 # génère frontend/dist/
```

Déployez le contenu de `frontend/dist/` derrière un serveur statique (Nginx, IIS,
CDN…) et pointez `VITE_API_URL` vers l'URL publique de l'API.

---

## Architecture

- **Backend** : architecture orientée services (`app/Services`), Form Requests,
  Policies, API Resources, Jobs, Notifications. Permissions via Spatie. Workflow et
  relances configurables en base.
- **Frontend** : pages / composants / layouts, service Axios centralisé
  (`src/services/api.js`), contexte d'authentification (`src/auth`), gestion des
  permissions côté UI.
