<x-layouts.guest>
    <div x-data="{ showPassword: false }">
        <img src="{{ config('plateforme.monogramme') }}" alt="" class="mb-4 hidden h-10 w-auto lg:block">
        <h2 class="mb-1 text-lg font-semibold text-slate-900">Connexion</h2>
        <p class="mb-6 text-sm text-slate-500">Entrez vos identifiants pour accéder à votre espace.</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            @if ($errors->any())
                <div class="flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0">
                        <circle cx="12" cy="12" r="9" /><line x1="12" y1="8" x2="12" y2="12.5" /><circle cx="12" cy="15.5" r="0.2" fill="currentColor" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div>
                <label for="username" class="label-field">Nom d'utilisateur</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <circle cx="12" cy="8" r="3.2" /><path d="M5.5 20c1-3.5 3.7-5.5 6.5-5.5s5.5 2 6.5 5.5" />
                        </svg>
                    </span>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                           class="input-field pl-10">
                </div>
            </div>

            <div>
                <label for="password" class="label-field">Mot de passe</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <rect x="5" y="11" width="14" height="9" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" />
                        </svg>
                    </span>
                    <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required
                           class="input-field pl-10 pr-10">
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M3 3l18 18" /><path d="M10.6 10.6a3 3 0 0 0 4.24 4.24" /><path d="M9.9 5.1A10.6 10.6 0 0 1 12 5c6.5 0 10 7 10 7a13.2 13.2 0 0 1-3.1 4" /><path d="M6.1 6.1C3.6 7.9 2 12 2 12s1.6 3.4 4.6 5.4" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 text-slate-600">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/40">
                    Se souvenir de moi
                </label>
            </div>

            <button type="submit" class="btn-primary w-full py-2.5">
                Se connecter
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                    <line x1="4" y1="12" x2="20" y2="12" /><polyline points="14 6 20 12 14 18" />
                </svg>
            </button>
        </form>
    </div>
</x-layouts.guest>
