<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    /**
     * Signature : on accepte tous les paramètres en option, sinon on demande en interactif.
     *
     * Exemples :
     *   php artisan admin:create
     *   php artisan admin:create --email=admin2@kardafrica.com --password=Strong2026!
     *   php artisan admin:create --email=admin2@kardafrica.com --name="John Doe" --phone=+24177123456
     */
    protected $signature = 'admin:create
                            {--email= : Email du nouvel admin}
                            {--password= : Mot de passe (laisse vide pour générer)}
                            {--name= : Nom complet}
                            {--phone= : Numéro de téléphone}
                            {--force : Met à jour si l\'email existe déjà}';

    protected $description = 'Crée un nouveau compte administrateur (interactif ou via options).';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Email du nouvel admin');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email invalide.');
            return self::FAILURE;
        }

        // Existe déjà ?
        $existing = User::where('email', $email)->first();
        if ($existing && !$this->option('force')) {
            $this->error("Un utilisateur avec l'email {$email} existe déjà.");
            $this->line("Utilise --force pour mettre à jour son mot de passe et le promouvoir admin.");
            return self::FAILURE;
        }

        $name     = $this->option('name')  ?: $this->ask('Nom complet', 'Admin KardAfrica');
        $phone    = $this->option('phone') ?: $this->ask('Téléphone (optionnel)', '+24100000000');

        $password = $this->option('password');
        if (!$password) {
            $generate = $this->confirm('Générer un mot de passe aléatoire fort ?', true);
            if ($generate) {
                $password = 'KA-' . Str::random(10) . '!';
            } else {
                $password = $this->secret('Mot de passe (min. 8 caractères)');
                if (strlen($password) < 8) {
                    $this->error('Le mot de passe doit faire au moins 8 caractères.');
                    return self::FAILURE;
                }
            }
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => $password, // hashé auto via cast
                'phone'             => $phone,
                'role'              => 'admin',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        $this->newLine();
        $this->info('==============================================');
        $this->info($existing ? '  ✓ Admin mis à jour' : '  ✓ Nouveau compte admin créé');
        $this->info('==============================================');
        $this->line("  Nom         : {$user->name}");
        $this->line("  Email       : {$user->email}");
        $this->line("  Mot de passe : {$password}");
        $this->line("  Téléphone   : {$user->phone}");
        $this->line("  URL         : " . url('/admin/login'));
        $this->warn('  ⚠ Note ce mot de passe maintenant — il ne sera plus affiché.');
        $this->info('==============================================');

        return self::SUCCESS;
    }
}
