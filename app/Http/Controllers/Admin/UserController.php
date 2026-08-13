<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount(['orders', 'userCards'])->latest();

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filtre par role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filtre par statut actif
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Formulaire de creation d'utilisateur
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Creer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:user,moderator,admin',
        ]);

        // Note: Le model User a 'password' => 'hashed' dans les casts
        // Laravel hash automatiquement, pas besoin de Hash::make()
        // M21 : role/is_active en affectation explicite (hors mass assignment)
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,
        ]);
        $user->role = $request->role;
        $user->is_active = true;
        $user->save();

        return redirect()->route('admin.users.show', $user)
            ->with('success', "L'utilisateur {$user->name} a ete cree avec succes.");
    }

    public function show(User $user)
    {
        $user->load([
            'orders' => function ($q) { $q->with('orderItems')->latest()->take(20); },
            'userCards' => function ($q) { $q->with('orderItem')->latest()->take(20); },
            'profile',
        ]);

        $user->loadCount(['orders', 'userCards', 'payments']);

        $totalSpent = $user->orders()
            ->where('payment_status', 'completed')
            ->sum('total_amount');

        $activeCardsCount = $user->userCards()->where('status', 'active')->count();
        $completedOrdersCount = $user->orders()->where('status', 'completed')->count();
        $lastOrderAt = $user->orders()->latest()->value('created_at');

        return view('admin.users.show', compact(
            'user', 'totalSpent', 'activeCardsCount', 'completedOrdersCount', 'lastOrderAt'
        ));
    }

    /**
     * Activer/Desactiver un utilisateur
     */
    public function toggleActive(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'active' : 'desactive';
        return back()->with('success', "L'utilisateur {$user->name} a ete {$status}.");
    }

    /**
     * Changer le role d'un utilisateur
     */
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:user,moderator,admin',
        ]);

        // M21 : role hors mass assignment (affectation directe)
        $user->role = $request->role;
        $user->save();

        return back()->with('success', "Le role de {$user->name} a ete mis a jour en '{$request->role}'.");
    }
}
