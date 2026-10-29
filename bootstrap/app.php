<?php

use App\Http\Middleware\CheckModuleAccess;
use App\Http\Middleware\EstSuperAdministrateur;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'module' => CheckModuleAccess::class,
            'super-admin' => EstSuperAdministrateur::class,
        ]);

        // Derriere un tunnel ou un hebergeur (Cloudflare, Render...), la requete arrive
        // via un proxy : on lui fait confiance pour detecter le https et generer des
        // liens https (sinon styles et scripts seraient bloques en "contenu mixte").
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
