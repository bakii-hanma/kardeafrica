<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use App\Support\VendorStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        // `volume_total` lisait la colonne `total_volume`, incrémentée à
        // l'encaissement mais JAMAIS décrémentée au remboursement : le profil
        // annonçait donc un volume supérieur à celui du dashboard, avec le même
        // libellé. Les deux écrans partagent désormais la définition de
        // VendorStats (livré uniquement, Carte Gabon incluse).
        $vendorStats = VendorStats::for($reseller);

        $stats = [
            'orders_total'      => $reseller->orders()->count(),
            'orders_completed'  => $reseller->orders()->where('status', 'completed')->count(),
            'volume_total'      => $vendorStats->volume(),
            'commission_total'  => (float) $reseller->total_commission_earned,
        ];

        $recentTransactions = $reseller->walletTransactions()->take(8)->get();

        // Place restante sous le plafond : borne le transfert de commissions.
        $walletHeadroom = max(0, (float) $reseller->max_wallet - (float) $reseller->wallet_balance);
        $transferable   = min((float) $reseller->commission_balance, $walletHeadroom);

        return view('vendor.profile', compact(
            'reseller', 'stats', 'recentTransactions', 'walletHeadroom', 'transferable'
        ));
    }

    /**
     * Met à jour les coordonnées du vendeur.
     *
     * L'écran était en lecture seule intégrale : impossible de corriger un
     * numéro ou d'enregistrer son compte Mobile Money sans passer par un admin.
     * Le code vendeur, les soldes, le taux de commission et le plafond restent
     * hors de portée — ce sont des données contractuelles.
     */
    public function update(Request $request)
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:120'],
            'phone'                 => ['required', 'string', 'max:30'],
            'email'                 => ['nullable', 'email', 'max:120', Rule::unique('resellers')->ignore($reseller->id)],
            'whatsapp_number'       => ['nullable', 'string', 'max:30'],
            'mobile_money_provider' => ['nullable', 'string', Rule::in(['airtel', 'moov'])],
            'mobile_money_account'  => ['nullable', 'string', 'max:30'],
        ], [
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
        ]);

        // Le téléphone est le canal du code de récupération : on le stocke au
        // format canonique de l'application (celui utilisé à l'envoi des OTP),
        // sinon un reset ne retrouverait plus le compte.
        $validated['phone'] = OtpService::normalizeGabon($validated['phone']);
        if (!empty($validated['whatsapp_number'])) {
            $validated['whatsapp_number'] = OtpService::normalizeGabon($validated['whatsapp_number']);
        }

        if ($validated['phone'] === '') {
            return back()->withInput()->withErrors(['phone' => 'Numéro de téléphone invalide.']);
        }

        // Unicité testée sur TOUTES les formes du numéro : la base mélange deux
        // conventions (avec et sans le zéro national), un simple égal laisserait
        // passer un doublon.
        $taken = \App\Models\Reseller::whereIn(
                'phone',
                \App\Http\Controllers\Vendor\PasswordResetController::phoneCandidates($validated['phone'])
            )
            ->where('id', '!=', $reseller->id)
            ->exists();
        if ($taken) {
            return back()->withInput()->withErrors(['phone' => 'Ce numéro est déjà utilisé par un autre compte.']);
        }

        $reseller->fill($validated)->save();

        return back()->with('success', 'Tes informations ont été mises à jour.');
    }

    /**
     * Change le mot de passe depuis l'espace connecté (l'ancien est exigé).
     */
    public function updatePassword(Request $request)
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'La confirmation ne correspond pas.',
            'password.min'       => 'Le mot de passe doit faire au moins 8 caractères.',
        ]);

        if (!Hash::check($request->input('current_password'), $reseller->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.']);
        }

        $reseller->password = $request->input('password');   // cast 'hashed'
        $reseller->save();

        Log::info('Revendeur : mot de passe modifié depuis l\'espace', ['reseller_id' => $reseller->id]);

        return back()->with('success', 'Ton mot de passe a été modifié.');
    }

    /**
     * Transfère tout ou partie des commissions gagnées vers le solde de vente.
     *
     * C'était jusqu'ici un cul-de-sac : le vendeur accumulait un
     * `commission_balance` sans aucun moyen d'en disposer.
     */
    public function transferCommission(Request $request)
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:100',
        ], [
            'amount.min' => 'Le montant minimum est de 100 FCFA.',
        ]);

        try {
            $result = $reseller->transferCommissionToWallet((float) $validated['amount']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Transfert de commissions échoué', [
                'reseller_id' => $reseller->id,
                'amount'      => $validated['amount'],
                'error'       => $e->getMessage(),
            ]);
            return back()->with('error', "Le transfert n'a pas pu être effectué. Réessaie dans un instant.");
        }

        return back()->with('success', sprintf(
            '%s FCFA transférés. Ton solde de vente est maintenant de %s FCFA.',
            number_format((float) $validated['amount'], 0, ',', ' '),
            number_format($result['wallet_balance'], 0, ',', ' ')
        ));
    }
}
