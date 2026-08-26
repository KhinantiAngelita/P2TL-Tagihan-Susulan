<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Daftar semua user hasil registrasi (bukan akun super_admin lain),
     * dengan pencarian nama/email + filter status aktif/nonaktif.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status'); // 'aktif' | 'nonaktif' | null (semua)
        $search = trim((string) $request->query('q'));

        $baseQuery = User::query()->where('role', 'user');

        $totalUser     = (clone $baseQuery)->count();
        $totalAktif    = (clone $baseQuery)->aktif()->count();
        $totalNonaktif = (clone $baseQuery)->nonaktif()->count();

        $users = (clone $baseQuery)
            ->when($search !== '', fn ($q) => $q->where(
                fn ($q2) => $q2->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($status === 'aktif', fn ($q) => $q->aktif())
            ->when($status === 'nonaktif', fn ($q) => $q->nonaktif())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact(
            'users', 'status', 'search', 'totalUser', 'totalAktif', 'totalNonaktif'
        ));
    }

    /**
     * Form buat akun baru — hanya bisa diakses super admin (sudah
     * dibatasi middleware 'super_admin' di route). Ini menggantikan
     * halaman register publik yang sudah tidak ada lagi.
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * Simpan akun baru yang dibuat oleh super admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'in:user,super_admin'],
        ]);

        User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'role'      => $validated['role'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$validated['name']} berhasil dibuat.");
    }

    /**
     * Detail profil salah satu user — password & field sensitif lain
     * gak pernah dikirim ke view (sudah di-hide di model User).
     */
    public function show(User $user): View
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Toggle aktif/nonaktif. Super admin gak bisa nonaktifin akunnya sendiri.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['status' => 'Tidak bisa menonaktifkan akun sendiri.']);
        }

        $user->update([
            'is_active'         => ! $user->is_active,
            'status_changed_at' => now(),
        ]);

        return back()->with(
            'success',
            $user->is_active ? "Akun {$user->name} diaktifkan." : "Akun {$user->name} dinonaktifkan."
        );
    }
}