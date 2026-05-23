<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NewsletterController extends Controller
{
    /**
     * POST /newsletter/subscribe
     * Inscrit un email a la newsletter. Idempotent : si deja present mais desabonne,
     * reabonne. Si deja actif, renvoie un message neutre.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email'  => 'required|email|max:255',
            'source' => 'nullable|string|max:50',
        ], [
            'email.required' => 'Adresse email requise.',
            'email.email'    => 'Adresse email invalide.',
        ]);

        $email = strtolower(trim($validated['email']));

        try {
            $existing = NewsletterSubscriber::where('email', $email)->first();

            if ($existing) {
                if ($existing->is_active) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Vous êtes déjà abonné. Merci !',
                        'already' => true,
                    ]);
                }
                $existing->resubscribe();
                return response()->json([
                    'success' => true,
                    'message' => 'Bon retour ! Votre abonnement a été réactivé.',
                ]);
            }

            $sub = NewsletterSubscriber::create([
                'email'      => $email,
                'source'     => $validated['source'] ?? 'footer',
                'locale'     => app()->getLocale() ?: 'fr',
                'is_active'  => true,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Merci ! Vous recevrez nos prochaines offres par email.',
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Newsletter subscribe failed', ['error' => $e->getMessage(), 'email' => $email]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue. Réessayez dans un instant.',
            ], 500);
        }
    }

    /**
     * GET /newsletter/unsubscribe/{token}
     * Page de confirmation de désinscription.
     */
    public function unsubscribe(string $token)
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if (!$subscriber) {
            return view('newsletter.unsubscribe', [
                'status'  => 'not_found',
                'message' => 'Lien invalide ou expiré.',
            ]);
        }

        if ($subscriber->is_active) {
            $subscriber->unsubscribe();
            return view('newsletter.unsubscribe', [
                'status'  => 'success',
                'message' => 'Vous êtes désinscrit avec succès.',
                'email'   => $subscriber->email,
            ]);
        }

        return view('newsletter.unsubscribe', [
            'status'  => 'already',
            'message' => 'Vous êtes déjà désinscrit.',
            'email'   => $subscriber->email,
        ]);
    }
}
