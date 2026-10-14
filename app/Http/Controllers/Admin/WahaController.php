<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WahaService;
use Illuminate\Http\Request;

/**
 * Admin\WahaController
 * ===
 * Connexion du numéro WhatsApp via WAHA (QR code) + état de la session, pour
 * l'envoi des codes OTP. Le QR et les statuts sont PROXYfiés côté serveur :
 * la clé API WAHA ne transite jamais par le navigateur.
 */
class WahaController extends Controller
{
    public function __construct(private WahaService $waha) {}

    public function index()
    {
        return view('admin.waha.index', [
            'configured' => $this->waha->isConfigured(),
        ]);
    }

    /** Statut JSON (poll par la page admin). */
    public function status()
    {
        return response()->json($this->waha->status());
    }

    /** Proxy du QR (PNG) — image affichée dans l'admin. */
    public function qr()
    {
        $qr = $this->waha->qrPng();
        if (! $qr['ok'] || empty($qr['body'])) {
            // Pas de QR disponible (session déjà connectée ou pas prête).
            return response()->noContent(409);
        }
        return response($qr['body'], 200)
            ->header('Content-Type', $qr['contentType'] ?: 'image/png')
            ->header('Cache-Control', 'no-store');
    }

    public function start(Request $request)
    {
        return response()->json(['ok' => $this->waha->start()]);
    }

    public function restart(Request $request)
    {
        return response()->json(['ok' => $this->waha->restart()]);
    }

    public function logout(Request $request)
    {
        return response()->json(['ok' => $this->waha->logout()]);
    }

    /** Envoi d'un message de test (vérifier la connexion). */
    public function test(Request $request)
    {
        $data = $request->validate(['phone' => 'required|string|max:20']);
        $phone = \App\Support\Phone::accountKey($data['phone']);
        if (! $phone) {
            return response()->json(['ok' => false, 'message' => 'Numéro invalide.'], 422);
        }
        $ok = $this->waha->sendText($phone, "KardAfrica — message de test WhatsApp ✅\nLa connexion fonctionne.");
        return response()->json(['ok' => $ok]);
    }

    // ---------------------------------------------------------------------
    //  CHAT — liste des conversations / messages / réponse
    // ---------------------------------------------------------------------

    /** Liste des conversations récentes (proxy WAHA, normalisée). */
    public function chats()
    {
        $raw = $this->waha->listChats(40);
        $chats = [];
        foreach ($raw as $c) {
            $c = (array) $c;
            $id = $c['id'] ?? ($c['chatId'] ?? null);
            if (is_array($id)) {
                $id = $id['_serialized'] ?? ($id['user'] ?? null);
            }
            if (! $id) {
                continue;
            }
            $id = (string) $id;
            // On ignore les diffusions/statuts.
            if (str_contains($id, 'status@broadcast')) {
                continue;
            }
            $chats[] = [
                'id'      => $id,
                'name'    => $c['name'] ?? ($c['pushName'] ?? ($c['formattedTitle'] ?? self::prettyId($id))),
                'isGroup' => str_contains($id, '@g.us'),
                'preview' => isset($c['lastMessage']['body']) ? (string) $c['lastMessage']['body'] : '',
                'ts'      => $c['timestamp'] ?? ($c['conversationTimestamp'] ?? null),
            ];
        }
        return response()->json(['ok' => true, 'chats' => $chats]);
    }

    /** Messages d'une conversation. */
    public function messages(Request $request)
    {
        $data = $request->validate(['chatId' => 'required|string|max:60']);
        $raw = $this->waha->getMessages($data['chatId'], 40);
        $out = [];
        foreach ($raw as $m) {
            $m = (array) $m;
            $out[] = [
                'id'      => $m['id'] ?? null,
                'fromMe'  => (bool) ($m['fromMe'] ?? false),
                'body'    => (string) ($m['body'] ?? ($m['caption'] ?? '')),
                'ts'      => $m['timestamp'] ?? null,
                'hasMedia'=> (bool) ($m['hasMedia'] ?? false),
            ];
        }
        return response()->json(['ok' => true, 'messages' => $out]);
    }

    /** Répondre dans une conversation. */
    public function sendChat(Request $request)
    {
        $data = $request->validate([
            'chatId' => 'required|string|max:60',
            'text'   => 'required|string|max:4000',
        ]);
        $ok = $this->waha->sendToChat($data['chatId'], $data['text']);
        return response()->json(['ok' => $ok]);
    }

    // ---------------------------------------------------------------------
    //  ENVOI GROUPÉ (campagne) + MESSAGE D'EXCUSES
    // ---------------------------------------------------------------------

    /** Diffusion d'un message à une liste de numéros saisis. */
    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'recipients' => 'required|string|max:8000', // numéros séparés par , ; espace ou retour ligne
            'text'       => 'required|string|max:4000',
        ]);

        $tokens = preg_split('/[\s,;]+/', trim($data['recipients'])) ?: [];
        $sent = 0; $failed = 0; $invalid = [];
        foreach ($tokens as $tok) {
            if ($tok === '') {
                continue;
            }
            $phone = \App\Support\Phone::accountKey($tok);
            if (! $phone) {
                $invalid[] = $tok;
                continue;
            }
            $this->waha->sendText($phone, $data['text']) ? $sent++ : $failed++;
            usleep(300000); // throttle anti-ban (0,3 s)
        }

        return response()->json([
            'ok'      => $sent > 0,
            'sent'    => $sent,
            'failed'  => $failed,
            'invalid' => $invalid,
        ]);
    }

    /**
     * Destinataires du message d'excuses : clients ayant une commande restée en
     * attente/en cours (non livrée) sur les 10 derniers jours, avec un numéro.
     */
    public function pendingContacts()
    {
        return response()->json(['ok' => true, 'contacts' => $this->pendingOrderUsers()]);
    }

    /** Envoie le message d'excuses aux clients concernés. */
    public function apology(Request $request)
    {
        $retry = url('/boutique');
        $sent = 0; $failed = 0; $recipients = [];
        foreach ($this->pendingOrderUsers() as $c) {
            $user = \App\Models\User::find($c['id']);
            if (! $user) {
                continue;
            }
            \App\Support\WhatsAppCardNotifier::sendApology($user, $retry) ? $sent++ : $failed++;
            $recipients[] = $c['name'];
            usleep(400000);
        }
        return response()->json(['ok' => $sent > 0, 'sent' => $sent, 'failed' => $failed, 'recipients' => $recipients]);
    }

    /** @return array<int,array{id:int,name:string,phone:string}> */
    private function pendingOrderUsers(): array
    {
        $since = now()->subDays(10);
        $userIds = \App\Models\Order::query()
            ->whereIn('status', [\App\Models\Order::STATUS_PENDING, \App\Models\Order::STATUS_PROCESSING])
            ->where('created_at', '>=', $since)
            ->pluck('user_id')
            ->filter()
            ->unique();

        $out = [];
        foreach (\App\Models\User::whereIn('id', $userIds)->get() as $u) {
            if (empty($u->phone)) {
                continue;
            }
            $out[] = [
                'id'    => $u->id,
                'name'  => $u->name ?: \App\Support\Phone::display($u->phone),
                'phone' => \App\Support\Phone::masked($u->phone),
            ];
        }
        return $out;
    }

    private static function prettyId(string $id): string
    {
        $n = preg_replace('/@.*/', '', $id);
        return '+' . $n;
    }
}
