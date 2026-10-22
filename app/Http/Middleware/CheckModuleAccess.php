<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    /**
     * Verifie que l'utilisateur connecte a le droit d'acceder au module demande,
     * en se basant sur la table access_rules (equivalent de la feuille DROITS_ACCES du classeur).
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, "Authentification requise.");
        }

        // Pages des entreprises : le super-administrateur est renvoye vers son espace.
        if ($user->est_super_administrateur) {
            return redirect()->route('super-admin.entreprises')
                ->with('status', "L'espace super-administrateur gère les entreprises mais n'ouvre pas leurs données. Pour les consulter, connectez-vous avec un compte de l'entreprise.");
        }

        if (! $user->actif) {
            abort(403, "Ce compte utilisateur est desactive. Contactez un administrateur.");
        }

        if (! $user->peutAccederA($module)) {
            abort(403, "Vous n'avez pas les droits necessaires pour acceder a ce module ({$module}).");
        }

        return $next($request);
    }
}
