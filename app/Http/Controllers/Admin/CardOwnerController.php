<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardOwner;
use App\Models\MerchantCard;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin\CardOwnerController
 * ===
 * CRUD des propriétaires de cartes locales (Carte Gabon). L'admin crée le
 * compte du propriétaire (commerçant) et lui assigne ensuite des cartes
 * dans /admin/merchant-cards. Le propriétaire se connecte ensuite sur
 * /proprietaire/connexion pour son dashboard et le scan au comptoir.
 */
class CardOwnerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $query = CardOwner::query()->withCount('cards');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status === 'active')   $query->where('is_active', true);
        if ($status === 'inactive') $query->where('is_active', false);

        $owners = $query->latest('created_at')->paginate(24)->withQueryString();

        $stats = [
            'total'    => CardOwner::count(),
            'active'   => CardOwner::where('is_active', true)->count(),
            'inactive' => CardOwner::where('is_active', false)->count(),
        ];

        return view('admin.card-owners.index', compact('owners', 'stats', 'search', 'status'));
    }

    public function create()
    {
        return view('admin.card-owners.create', [
            'owner'      => new CardOwner(['is_active' => true]),
            'categories' => MerchantCard::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);

        // Mot de passe : utilisé tel quel si fourni, sinon généré (8 chiffres)
        // pour que l'admin puisse le transmettre au commerçant.
        $plainPassword = !empty($data['password'])
            ? $data['password']
            : (string) random_int(10_000_000, 99_999_999);

        $owner = new CardOwner($data);
        $owner->password = Hash::make($plainPassword);
        $owner->slug     = CardOwner::uniqueSlug($data['business_name']);

        if ($request->hasFile('logo')) {
            $owner->logo_url = $this->storeLogo($request->file('logo'));
        }

        $owner->save();

        return redirect()
            ->route('admin.card-owners.show', $owner)
            ->with('success', "Propriétaire « {$owner->business_name} » créé.")
            ->with('temp_password', $plainPassword);
    }

    public function show(CardOwner $cardOwner)
    {
        $cardOwner->loadCount('cards')->load(['cards' => function ($q) {
            $q->latest('created_at')->take(20);
        }]);

        return view('admin.card-owners.show', [
            'owner'      => $cardOwner,
            'categories' => MerchantCard::CATEGORIES,
        ]);
    }

    public function edit(CardOwner $cardOwner)
    {
        return view('admin.card-owners.edit', [
            'owner'      => $cardOwner,
            'categories' => MerchantCard::CATEGORIES,
        ]);
    }

    public function update(Request $request, CardOwner $cardOwner)
    {
        $data = $this->validated($request, $cardOwner);

        if (!empty($data['password'])) {
            $cardOwner->password = Hash::make($data['password']);
        }
        unset($data['password']);

        if ($request->hasFile('logo')) {
            $this->deleteLogo($cardOwner->logo_url);
            $data['logo_url'] = $this->storeLogo($request->file('logo'));
        }

        // Slug recalculé si business_name change
        if ($data['business_name'] !== $cardOwner->business_name) {
            $data['slug'] = CardOwner::uniqueSlug($data['business_name'], $cardOwner->id);
        }

        $cardOwner->update($data);

        return redirect()
            ->route('admin.card-owners.show', $cardOwner)
            ->with('success', 'Propriétaire mis à jour.');
    }

    public function destroy(CardOwner $cardOwner)
    {
        // Refuse si des cartes lui sont rattachées (on désactive plutôt)
        if ($cardOwner->cards()->exists()) {
            $cardOwner->update(['is_active' => false]);
            return redirect()
                ->route('admin.card-owners.index')
                ->with('success', "Propriétaire désactivé (des cartes lui sont attachées, suppression impossible).");
        }

        $this->deleteLogo($cardOwner->logo_url);
        $cardOwner->delete();

        return redirect()
            ->route('admin.card-owners.index')
            ->with('success', 'Propriétaire supprimé.');
    }

    // ============================================================
    // API JSON — utilisée par le form admin merchant-cards pour
    // créer un propriétaire à la volée sans quitter la page.
    // ============================================================

    public function quickStore(Request $request)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'contact_name'  => ['required', 'string', 'max:120'],
            'email'         => ['required', 'email', 'max:190', 'unique:card_owners,email'],
            'phone'         => ['required', 'string', 'max:30'],
            'city'          => ['nullable', 'string', 'max:100'],
            'business_type' => ['nullable', 'string', Rule::in(array_keys(MerchantCard::CATEGORIES))],
        ]);

        // Mot de passe provisoire = 8 chiffres. À communiquer au commerçant.
        $tempPassword = (string) random_int(10_000_000, 99_999_999);

        $owner = CardOwner::create([
            'business_name' => $data['business_name'],
            'contact_name'  => $data['contact_name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'city'          => $data['city'] ?? null,
            'business_type' => $data['business_type'] ?? null,
            'password'      => Hash::make($tempPassword),
            'is_active'     => true,
        ]);

        return response()->json([
            'success'         => true,
            'owner'           => [
                'id'            => $owner->id,
                'business_name' => $owner->business_name,
                'contact_name'  => $owner->contact_name,
                'email'         => $owner->email,
                'phone'         => $owner->phone,
            ],
            'temp_password'   => $tempPassword,
        ]);
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function validated(Request $request, ?CardOwner $existing): array
    {
        $rules = [
            'business_name'   => ['required', 'string', 'max:120'],
            'contact_name'    => ['required', 'string', 'max:120'],
            'email'           => [
                'required', 'email', 'max:190',
                Rule::unique('card_owners', 'email')->ignore($existing?->id),
            ],
            'phone'           => ['required', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'password'        => ['nullable', 'string', 'min:6', 'max:120'],
            'city'            => ['nullable', 'string', 'max:100'],
            'address'         => ['nullable', 'string', 'max:255'],
            'business_type'   => ['nullable', 'string', Rule::in(array_keys(MerchantCard::CATEGORIES))],
            'logo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active'       => ['nullable', 'boolean'],
        ];

        $data = $request->validate($rules);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        unset($data['logo']);
        return $data;
    }

    /**
     * Comme pour les visuels de carte, on écrit dans la racine doc-root nginx
     * (base_path) car public_path() n'est pas exposé sur SiteGround.
     */
    private function storeLogo(UploadedFile $file): string
    {
        $relDir = 'card-owners/logos';
        $absDir = base_path($relDir);
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0755, true);
        }
        $filename = Str::random(40).'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $file->move($absDir, $filename);
        return $relDir.'/'.$filename;
    }

    /**
     * Réinitialise le mot de passe d'un propriétaire (par un admin).
     * Le cast 'hashed' du modèle CardOwner hashe automatiquement.
     */
    public function resetPassword(Request $request, CardOwner $cardOwner)
    {
        $request->validate(['password' => 'required|string|min:8|confirmed']);
        $cardOwner->update(['password' => $request->password]);

        return back()->with('success', "Mot de passe réinitialisé pour {$cardOwner->name}.");
    }

    private function deleteLogo(?string $relPath): void
    {
        if (!$relPath) return;
        $abs = base_path($relPath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
