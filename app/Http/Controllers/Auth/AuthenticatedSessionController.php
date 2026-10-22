<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(): \Illuminate\View\View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'username' => "Identifiants incorrects.",
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user->actif) {
            Auth::logout();
            throw ValidationException::withMessages([
                'username' => "Ce compte est desactive.",
            ]);
        }

        if (! $user->est_super_administrateur && ! $user->entreprise?->actif) {
            Auth::logout();
            throw ValidationException::withMessages([
                'username' => "Cette entreprise n'est plus active. Contactez l'exploitant de la plateforme.",
            ]);
        }

        $user->forceFill(['derniere_connexion' => now()])->save();

        ActivityLog::enregistrer('connexion', 'AUTH', $user->id, null, null, $user->username);

        if ($user->est_super_administrateur) {
            return redirect()->intended(route('super-admin.entreprises'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        ActivityLog::enregistrer('deconnexion', 'AUTH', $user?->id, null, null, $user?->username);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
