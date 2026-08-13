<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardOwner;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Support\OwnerEarnings;
use App\Models\MerchantSettlement;
use App\Services\WhapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Back-office : modération des comptes pro/commerçant (CardOwner).
 *
 * Valide (définitif), demande des pièces (WhatsApp), refuse ou suspend un compte.
 * Sert aussi les pièces KYC depuis le disque privé, uniquement à l'admin authentifié.
 */
class ProprietaireController extends Controller
{
    public function __construct(private WhapiService $whapi) {}

    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'provisional');

        $counts = [
            'provisional'    => CardOwner::where('status', CardOwner::STATUS_PROVISIONAL)->count(),
            'docs_requested' => CardOwner::where('status', CardOwner::STATUS_DOCS_REQUESTED)->count(),
            'active'         => CardOwner::where('status', CardOwner::STATUS_ACTIVE)->count(),
            'rejected'       => CardOwner::where('status', CardOwner::STATUS_REJECTED)->count(),
            'suspended'      => CardOwner::where('status', CardOwner::STATUS_SUSPENDED)->count(),
        ];

        $query = CardOwner::query()->latest('kyc_submitted_at')->latest('id');
        if (array_key_exists($status, $counts)) {
            $query->where('status', $status);
        }
        $owners = $query->paginate(20)->withQueryString();

        return view('admin.proprietaires.index', compact('owners', 'counts', 'status'));
    }

    public function show(CardOwner $proprietaire)
    {
        $proprietaire->loadCount('cards');

        return view('admin.proprietaires.show', [
            'owner'       => $proprietaire,
            'earnings'    => OwnerEarnings::for($proprietaire),
            'settlements' => MerchantSettlement::where('card_owner_id', $proprietaire->id)
                ->with('recordedBy:id,name')
                ->latest('settled_at')
                ->paginate(15),
        ]);
    }

    /**
     * Enregistre un versement effectué à un commerçant.
     *
     * Les reversements passent par Mobile Money ou en espèces, hors application :
     * on ne les automatise pas, on les rend vérifiables. Sans cette trace, le
     * commerçant voyait un cumul de gains sans savoir ce qui lui avait été réglé.
     */
    public function storeSettlement(Request $request, CardOwner $proprietaire)
    {
        $data = $request->validate([
            'amount'    => ['required', 'numeric', 'min:1'],
            'method'    => ['required', 'string', Rule::in(array_keys(MerchantSettlement::METHODS))],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes'     => ['nullable', 'string', 'max:1000'],
            'settled_at'=> ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $du = OwnerEarnings::for($proprietaire)->outstanding();

        // Avertissement, pas blocage : une avance sur des ventes à venir est une
        // décision commerciale légitime, elle doit juste être vue.
        $avance = (float) $data['amount'] > $du;

        MerchantSettlement::create([
            'card_owner_id' => $proprietaire->id,
            'amount'        => $data['amount'],
            'method'        => $data['method'],
            'reference'     => $data['reference'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'recorded_by'   => Auth::id(),
            'settled_at'    => $data['settled_at'] ?? now(),
        ]);

        Log::info('Admin: versement commerçant enregistré', [
            'card_owner_id' => $proprietaire->id,
            'montant'       => (float) $data['amount'],
            'du_avant'      => $du,
            'par'           => Auth::id(),
        ]);

        return back()->with('success', $avance
            ? 'Versement enregistré. Attention : il dépasse le solde dû, le commerçant est en avance.'
            : 'Versement enregistré.');
    }

    /** Sert une pièce KYC depuis le disque privé (admin uniquement). */
    public function document(CardOwner $proprietaire, string $which)
    {
        $path = match ($which) {
            'id'       => $proprietaire->id_document_path,
            'business' => $proprietaire->business_document_path,
            default    => null,
        };
        abort_if(!$path || !Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    /** Validation DÉFINITIVE du compte. */
    public function approve(Request $request, CardOwner $proprietaire)
    {
        $proprietaire->status      = CardOwner::STATUS_ACTIVE;
        $proprietaire->is_active   = true;
        $proprietaire->reviewed_by = Auth::id();
        $proprietaire->reviewed_at = now();
        $proprietaire->review_notes = $request->input('note');
        $proprietaire->docs_requested_note = null;
        $proprietaire->save();

        $this->whapi->sendText($proprietaire->whatsapp_number,
            "✅ KardAfrica : votre compte pro « {$proprietaire->business_name} » est validé ! "
            . "Vous pouvez créer et soumettre vos cartes cadeaux à la publication.");

        return back()->with('success', "Compte « {$proprietaire->business_name} » validé.");
    }

    /** Demande de pièces complémentaires (notifiée par WhatsApp). */
    public function requestDocs(Request $request, CardOwner $proprietaire)
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $proprietaire->status = CardOwner::STATUS_DOCS_REQUESTED;
        $proprietaire->docs_requested_note = $data['note'];
        $proprietaire->reviewed_by = Auth::id();
        $proprietaire->reviewed_at = now();
        // is_active reste true : le pro garde l'accès pour re-déposer ses pièces.
        $proprietaire->save();

        $this->whapi->sendText($proprietaire->whatsapp_number,
            "📄 KardAfrica : pour valider « {$proprietaire->business_name} », il nous faut :\n"
            . $data['note'] . "\n"
            . "Reconnecte-toi à ton espace pour mettre à jour ton dossier : " . route('pro.kyc.show'));

        return back()->with('success', 'Demande de pièces envoyée au commerçant (WhatsApp).');
    }

    /** Refus du dossier. */
    public function reject(Request $request, CardOwner $proprietaire)
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $proprietaire->status = CardOwner::STATUS_REJECTED;
        $proprietaire->is_active = false;
        $proprietaire->review_notes = $data['note'];
        $proprietaire->reviewed_by = Auth::id();
        $proprietaire->reviewed_at = now();
        $proprietaire->save();

        $this->whapi->sendText($proprietaire->whatsapp_number,
            "❌ KardAfrica : votre dossier « {$proprietaire->business_name} » n'a pas pu être validé.\n"
            . "Motif : " . $data['note']);

        return back()->with('success', 'Dossier refusé, commerçant notifié.');
    }

    /** Suspension d'un compte actif. */
    public function suspend(Request $request, CardOwner $proprietaire)
    {
        $proprietaire->status = CardOwner::STATUS_SUSPENDED;
        $proprietaire->is_active = false;
        $proprietaire->reviewed_by = Auth::id();
        $proprietaire->reviewed_at = now();
        $proprietaire->review_notes = $request->input('note');
        $proprietaire->save();

        return back()->with('success', 'Compte suspendu.');
    }
}
