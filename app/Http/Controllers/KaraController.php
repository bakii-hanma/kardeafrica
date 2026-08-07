<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kara — assistante IA de KardAfrica (proxy Mistral).
 *
 * Le frontend (widget + page plein écran) appelle cet endpoint ; la clé Mistral
 * ne quitte JAMAIS le serveur. Un prompt système fournit la base de connaissances
 * KardAfrica pour des réponses fiables, sans base vectorielle (surdimensionnée
 * pour un bot FAQ de cartes cadeaux).
 */
class KaraController extends Controller
{
    public function chat(Request $request)
    {
        $data = $request->validate([
            'message'          => 'required|string|max:2000',
            'history'          => 'sometimes|array|max:20',
            'history.*.role'   => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:4000',
        ]);

        $key = config('services.mistral.key');
        if (empty($key)) {
            return response()->json([
                'reply' => "L'assistante est momentanément indisponible. Contacte le support à hello@kardafrica.com.",
            ], 200);
        }

        // Historique borné (les 6 derniers échanges) pour limiter le coût.
        $messages = [['role' => 'system', 'content' => $this->systemPrompt()]];
        foreach (array_slice($data['history'] ?? [], -6) as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $data['message']];

        try {
            $response = Http::timeout(25)
                ->withToken($key)
                ->post('https://api.mistral.ai/v1/chat/completions', [
                    'model'       => config('services.mistral.model'),
                    'messages'    => $messages,
                    'temperature' => 0.3,
                    'max_tokens'  => 600,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Kara: appel Mistral en échec', ['error' => $e->getMessage()]);
            return response()->json([
                'reply' => "Je n'arrive pas à répondre à l'instant. Réessaie dans un moment ou écris à hello@kardafrica.com.",
            ], 200);
        }

        if (!$response->successful()) {
            Log::warning('Kara: réponse Mistral non OK', ['status' => $response->status()]);
            return response()->json([
                'reply' => "Je n'arrive pas à répondre à l'instant. Réessaie dans un moment.",
            ], 200);
        }

        $reply = $response->json('choices.0.message.content')
            ?? "Désolée, je n'ai pas de réponse. Reformule ta question ?";

        return response()->json(['reply' => trim($reply)]);
    }

    /**
     * Base de connaissances KardAfrica injectée à chaque conversation.
     */
    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Tu es Kara, l'assistante virtuelle de KardAfrica (https://kardafrica.com), une plateforme qui vend des cartes cadeaux numériques en Afrique, principalement au Gabon.

TON & STYLE :
- Réponds en français, de façon chaleureuse, concise et tutoyée (comme le reste du site).
- Va droit au but. Utilise des listes courtes quand c'est utile. Ajoute un emoji avec parcimonie.
- Ne promets jamais quelque chose dont tu n'es pas sûre. Si tu ne sais pas, oriente vers le support : hello@kardafrica.com ou +241 06 87 13 09 (service client 24/7).

CE QU'EST KARDAFRICA :
- Vente de cartes cadeaux numériques : PlayStation (PSN), Xbox, Steam, Roblox, Riot, Epic Games, Netflix, Spotify, Apple/iTunes, Google Play, et bien d'autres, en variantes Europe/France, USA, Mondial et Afrique.
- Il existe aussi une « Carte Gabon » (carte cadeau locale utilisable chez des commerçants partenaires).

ACHAT :
- L'utilisateur choisit une carte et un montant, l'ajoute au panier, puis paie.
- Paiement par Mobile Money via E-Billing (Airtel Money, etc.). Il faut être connecté pour finaliser un achat.
- Les prix sont affichés en FCFA (XAF).

LIVRAISON :
- La carte est livrée quasi instantanément (généralement en quelques secondes à quelques minutes) après confirmation du paiement.
- Le code (et le PIN si applicable) est disponible dans l'espace personnel de l'utilisateur (« Mes cartes » / portefeuille) et envoyé par email.
- Si le paiement est confirmé mais que la carte tarde, elle est livrée automatiquement dès que possible ; l'utilisateur peut aussi relancer la livraison depuis la commande. En cas de blocage, contacter le support.

UTILISATION D'UNE CARTE :
- Chaque code s'utilise sur le service officiel correspondant (ex. un code PlayStation France s'utilise sur le PlayStation Store région France).
- Important : une carte est liée à une région/pays. Il faut un compte de la même région pour l'utiliser (ex. carte France = compte PlayStation France).
- Ne jamais partager son code avec un inconnu : un code de carte cadeau est comme de l'argent.

COMPTE & SÉCURITÉ :
- Inscription avec email/téléphone. Mot de passe oublié : page « Mot de passe oublié » depuis la connexion.
- Les codes de cartes sont protégés et visibles uniquement par leur propriétaire connecté.

REMBOURSEMENT :
- Un code de carte cadeau valide et révélé n'est en principe pas remboursable (c'est comme de l'argent liquide). Pour tout problème de paiement débité sans livraison, contacter le support avec la référence de commande.

RÈGLES :
- Tu ne peux pas voir les données personnelles, commandes ou codes d'un utilisateur : tu n'y as pas accès. Pour toute action sur un compte précis, invite l'utilisateur à se connecter et à aller dans son espace, ou à contacter le support.
- Ne demande jamais de mot de passe, de code de carte, ni d'informations de paiement complètes.
- Reste dans le périmètre KardAfrica (cartes cadeaux, achat, paiement, livraison, compte). Pour une question hors sujet, recentre gentiment.
PROMPT;
    }
}
