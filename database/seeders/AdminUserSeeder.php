<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Crée (ou met à jour) le compte admin par défaut.
     *
     * Personnalise les valeurs ci-dessous, ou — mieux — passe-les en
     * variables d'environnement dans .env :
     *
     *   ADMIN_EMAIL=admin@kardafrica.com
     *   ADMIN_PASSWORD=motdepasse-fort
     *   ADMIN_NAME="Admin KardAfrica"
     *   ADMIN_PHONE=+24100000000
     *
     * Lancement :  php artisan db:seed --class=AdminUserSeeder --force
     */
    public function run(): void
    {
        $email    = env('ADMIN_EMAIL',    'admin@kardafrica.com');
        $password = env('ADMIN_PASSWORD');
        $name     = env('ADMIN_NAME',     'Admin KardAfrica');
        $phone    = env('ADMIN_PHONE',    '+24100000000');

        // Sécurité : aucun mot de passe par défaut en dur (le précédent
        // 'KardAfrica@2026' était dans le dépôt). On refuse de créer un admin
        // sans ADMIN_PASSWORD explicite plutôt que de semer un secret connu.
        if (empty($password)) {
            $this->command->error('ADMIN_PASSWORD absent du .env — création admin annulée.');
            return;
        }

        // Note : le cast 'password' => 'hashed' (User::casts) hashe automatiquement.
        // M21 : role/is_active hors mass assignment → affectation directe.
        $user = User::firstOrNew(['email' => $email]);
        $user->fill([
            'name'              => $name,
            'password'          => $password,
            'phone'             => $phone,
        ]);
        $user->role              = 'admin';
        $user->is_active         = true;
        $user->email_verified_at = now();
        $user->save();

        $this->command->info('');
        $this->command->info('==============================================');
        $this->command->info('  ✓ Compte admin prêt');
        $this->command->info('==============================================');
        $this->command->info("  Nom        : {$user->name}");
        $this->command->info("  Email      : {$user->email}");
        // Le mot de passe n'est JAMAIS imprimé (fuite via logs CI/déploiement).
        $this->command->info("  Rôle       : {$user->role}");
        $this->command->info("  URL        : " . url('/admin/login'));
        $this->command->info('==============================================');
    }
}
