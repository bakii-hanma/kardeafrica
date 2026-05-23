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
        $password = env('ADMIN_PASSWORD', 'KardAfrica@2026');
        $name     = env('ADMIN_NAME',     'Admin KardAfrica');
        $phone    = env('ADMIN_PHONE',    '+24100000000');

        // Note : le cast 'password' => 'hashed' (User::casts) hashe automatiquement
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => $password,
                'phone'             => $phone,
                'role'              => 'admin',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('');
        $this->command->info('==============================================');
        $this->command->info('  ✓ Compte admin prêt');
        $this->command->info('==============================================');
        $this->command->info("  Nom        : {$user->name}");
        $this->command->info("  Email      : {$user->email}");
        $this->command->info("  Mot de passe : {$password}");
        $this->command->info("  Rôle       : {$user->role}");
        $this->command->info("  URL        : " . url('/admin/login'));
        $this->command->warn('  ⚠ Change le mot de passe en production via les variables ADMIN_* du .env');
        $this->command->info('==============================================');
    }
}
