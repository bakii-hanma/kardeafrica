<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\CardOwner;
use App\Services\WhapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Étape 3 du parcours d'onboarding pro : dépôt du dossier KYC.
 *
 * À la soumission : accès PROVISOIRE (status=provisional) → le pro peut créer des
 * cartes (brouillons), en attendant la validation DÉFINITIVE par l'admin.
 *
 * Sécurité : les pièces (photo pièce d'identité, fiche circuit) sont des données
 * personnelles sensibles → stockées sur le disque PRIVÉ (storage/app/private/kyc),
 * jamais sur le doc-root public. Servies uniquement via une route admin authentifiée.
 */
class KycController extends Controller
{
    public function __construct(private WhapiService $whapi) {}

    /** Dossier en cours : depuis la session (parcours) ou le pro connecté (re-soumission). */
    private function currentOwner(Request $request): ?CardOwner
    {
        if (Auth::guard('card_owner')->check()) {
            return Auth::guard('card_owner')->user();
        }
        $id = $request->session()->get('pro.onboarding.owner_id');
        return $id ? CardOwner::find($id) : null;
    }

    public function show(Request $request)
    {
        $owner = $this->currentOwner($request);
        if (!$owner) {
            return redirect()->route('pro.register.show')
                ->withErrors(['email' => 'Session expirée, recommence l\'inscription.']);
        }
        // Le numéro doit d'abord être vérifié.
        if ($owner->status === CardOwner::STATUS_PENDING_OTP) {
            return redirect()->route('pro.verification.show');
        }

        return view('pro.kyc', ['owner' => $owner]);
    }

    public function submit(Request $request)
    {
        $owner = $this->currentOwner($request);
        if (!$owner) {
            return redirect()->route('pro.register.show')
                ->withErrors(['email' => 'Session expirée, recommence l\'inscription.']);
        }
        if ($owner->status === CardOwner::STATUS_PENDING_OTP) {
            return redirect()->route('pro.verification.show');
        }

        // À la première soumission les pièces sont requises ; en re-soumission
        // (docs_requested) elles restent requises pour repartir sur un dossier complet.
        $data = $request->validate([
            'city'      => ['required', 'string', 'max:100'],
            'quartier'  => ['required', 'string', 'max:120'],
            'address'   => ['nullable', 'string', 'max:255'],
            'geo_lat'   => ['nullable', 'numeric', 'between:-90,90'],
            'geo_lng'   => ['nullable', 'numeric', 'between:-180,180'],
            'id_document'       => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'business_document' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [], [
            'id_document'       => 'photo de la pièce du gérant',
            'business_document' => 'fiche circuit / justificatif d\'entreprise',
        ]);

        // Uploads sur disque PRIVÉ (jamais public).
        $idPath  = $this->storePrivate($request->file('id_document'));
        $bizPath = $this->storePrivate($request->file('business_document'));

        // Supprime d'anciennes pièces si re-soumission.
        $this->deletePrivate($owner->id_document_path);
        $this->deletePrivate($owner->business_document_path);

        $owner->fill([
            'city'     => $data['city'],
            'quartier' => $data['quartier'],
            'address'  => $data['address'] ?? null,
            'geo_lat'  => $data['geo_lat'] ?? null,
            'geo_lng'  => $data['geo_lng'] ?? null,
        ]);
        // Champs sensibles / état : hors mass-assignment (M21).
        $owner->id_document_path       = $idPath;
        $owner->business_document_path = $bizPath;
        $owner->kyc_submitted_at       = now();
        $owner->status                 = CardOwner::STATUS_PROVISIONAL;
        $owner->is_active              = true; // accès provisoire à l'espace de création
        $owner->docs_requested_note    = null;
        $owner->save();

        // Connexion automatique (accès provisoire immédiat).
        Auth::guard('card_owner')->login($owner);
        $request->session()->forget(['pro.onboarding.owner_id', 'pro.onboarding.phone']);
        $request->session()->regenerate();

        // Notifie l'admin qu'un dossier attend une validation.
        $this->whapi->notifyAdmin(
            "🆕 Nouveau dossier pro à valider\n"
            . "Entreprise : {$owner->business_name}\n"
            . "Gérant : {$owner->contact_name}\n"
            . "Ville : {$owner->city} ({$owner->quartier})\n"
            . "Valider dans l'admin : " . route('admin.proprietaires.show', $owner)
        );

        return redirect()->route('owner.dashboard')->with('success',
            'Dossier reçu ! Ton accès provisoire est activé — tu peux créer tes cartes. '
            . 'Elles seront publiées après validation de ton compte par notre équipe.');
    }

    /** Stocke un fichier uploadé sur le disque privé et retourne son chemin relatif. */
    private function storePrivate(\Illuminate\Http\UploadedFile $file): string
    {
        $name = Str::random(40) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('kyc', $name, 'local'); // storage/app/private/kyc/...
    }

    private function deletePrivate(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
