<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DaywatchProduct;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DaywatchController extends Controller
{
    public function index(Request $request)
    {
        $query = DaywatchProduct::query()->orderBy('sort_order')->orderByDesc('id');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('subtitle', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $products = $query->paginate(20)->withQueryString();

        $stats = [
            'total'    => DaywatchProduct::count(),
            'active'   => DaywatchProduct::where('is_active', true)->count(),
            'featured' => DaywatchProduct::where('is_featured', true)->count(),
            'avg_price'=> (int) DaywatchProduct::where('is_active', true)->avg('price_xaf'),
            'synced'   => DaywatchProduct::whereNotNull('plan_id')->count(),
            'synced_at'=> DaywatchProduct::max('synced_at'),
        ];

        return view('admin.daywatch.index', compact('products', 'stats'));
    }

    public function create()
    {
        return view('admin.daywatch.create', [
            'product' => new DaywatchProduct([
                'currency'      => 'XAF',
                'color'         => '#44A08D',
                'is_active'     => true,
                'is_featured'   => false,
                'duration_days' => 30,
                'features'      => [],
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->makeUniqueSlug($data['name']);
        $data['features'] = $this->parseFeatures($request->input('features_text', ''));

        $product = DaywatchProduct::create($data);

        return redirect()
            ->route('admin.daywatch.index')
            ->with('success', "Le produit Daywatch « {$product->name} » a été créé.");
    }

    public function edit(DaywatchProduct $daywatch)
    {
        return view('admin.daywatch.edit', ['product' => $daywatch]);
    }

    public function update(Request $request, DaywatchProduct $daywatch)
    {
        $data = $this->validateData($request, $daywatch->id);
        if ($data['name'] !== $daywatch->name) {
            $data['slug'] = $this->makeUniqueSlug($data['name'], $daywatch->id);
        }
        $data['features'] = $this->parseFeatures($request->input('features_text', ''));

        $daywatch->update($data);

        return redirect()
            ->route('admin.daywatch.index')
            ->with('success', "Le produit Daywatch « {$daywatch->name} » a été mis à jour.");
    }

    public function destroy(DaywatchProduct $daywatch)
    {
        $name = $daywatch->name;
        $daywatch->delete();

        return redirect()
            ->route('admin.daywatch.index')
            ->with('success', "Le produit Daywatch « {$name} » a été supprimé.");
    }

    /**
     * Rejoue `daywatch:sync` depuis l'admin.
     *
     * L'API Daywatch fait autorité sur les prix : ce bouton sert quand une
     * promotion vient de changer et qu'on ne veut pas attendre la passe de
     * 4h20. Le rendu de la commande est renvoyé tel quel, échec compris.
     */
    public function sync()
    {
        $code = Artisan::call('daywatch:sync');

        if ($code !== 0) {
            return back()->with('error', 'Synchronisation Daywatch impossible — API injoignable ou catalogue vide. Rien n\'a été modifié.');
        }

        return back()->with('success', 'Formules Daywatch synchronisées depuis leur API.');
    }

    public function toggleActive(DaywatchProduct $daywatch)
    {
        $daywatch->update(['is_active' => !$daywatch->is_active]);
        $status = $daywatch->is_active ? 'activé' : 'désactivé';
        return back()->with('success', "« {$daywatch->name} » a été {$status}.");
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'           => 'required|string|max:120',
            'subtitle'       => 'nullable|string|max:160',
            'description'    => 'nullable|string|max:2000',
            'duration_days'  => 'required|integer|min:1|max:3650',
            'price_xaf'      => 'required|integer|min:0',
            'currency'       => 'required|string|size:3',
            'color'          => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'image_url'      => 'nullable|url|max:500',
            'sort_order'     => 'nullable|integer|min:0|max:9999',
            'stock'          => 'nullable|integer|min:0',
            'is_active'      => 'sometimes|boolean',
            'is_featured'    => 'sometimes|boolean',
        ]) + [
            'is_active'   => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
            'sort_order'  => (int) $request->input('sort_order', 0),
        ];
    }

    private function parseFeatures(string $raw): array
    {
        return collect(preg_split('/\r?\n/', $raw))
            ->map(fn($l) => trim($l))
            ->filter(fn($l) => $l !== '')
            ->values()
            ->all();
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'daywatch';
        $slug = $base;
        $i = 2;
        while (DaywatchProduct::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
