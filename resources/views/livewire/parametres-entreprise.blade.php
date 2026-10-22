<div class="space-y-6">
    @php($lecture = ! auth()->user()->peutModifier('CONFIGURATION_ENTREPRISE'))
    <div class="card p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Coordonnées de l'entreprise</h2>
        <form wire:submit="enregistrer" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="label-field">Nom de l'entreprise</label>
                <input type="text" wire:model="nom_entreprise" class="mt-1 w-full input-field" @disabled($lecture)>
                @error('nom_entreprise') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-field">Sigle (utilisé dans le numéro des factures, ex. TMS)</label>
                <input type="text" wire:model="sigle" class="mt-1 w-full input-field" @disabled($lecture)>
                @error('sigle') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="label-field">Logo (affiché sur les factures et reçus imprimés)</label>
                <div class="mt-1 flex items-center gap-4">
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="Aperçu du logo" class="h-16 w-16 rounded-lg object-contain ring-1 ring-slate-200 bg-white">
                    @elseif ($logo_path)
                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($logo_path) }}" alt="Logo actuel" class="h-16 w-16 rounded-lg object-contain ring-1 ring-slate-200 bg-white">
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">Aucun logo</div>
                    @endif
                    @unless ($lecture)
                        <input type="file" wire:model="logo" accept="image/*" class="text-sm">
                    @endunless
                </div>
                @error('logo') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                <div wire:loading wire:target="logo" class="text-xs text-slate-400 mt-1">Televersement...</div>
            </div>
            <div class="sm:col-span-2">
                <label class="label-field">Activités</label>
                <textarea wire:model="activites_texte" rows="2" class="mt-1 w-full input-field" @disabled($lecture)></textarea>
            </div>
            <div>
                <label class="label-field">Téléphone</label>
                <input type="text" wire:model="telephone" class="mt-1 w-full input-field" @disabled($lecture)>
            </div>
            <div>
                <label class="label-field">Email</label>
                <input type="email" wire:model="email" class="mt-1 w-full input-field" @disabled($lecture)>
                @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-field">Ville</label>
                <input type="text" wire:model="ville" class="mt-1 w-full input-field" @disabled($lecture)>
            </div>
            <div>
                <label class="label-field">Adresse</label>
                <input type="text" wire:model="adresse" class="mt-1 w-full input-field" @disabled($lecture)>
            </div>
            <div>
                <label class="label-field">Régime fiscal</label>
                <input type="text" wire:model="regime_fiscal" class="mt-1 w-full input-field" @disabled($lecture)>
            </div>
            <div>
                <label class="label-field">Division fiscale</label>
                <input type="text" wire:model="division_fiscale" class="mt-1 w-full input-field" @disabled($lecture)>
            </div>
            <div>
                <label class="label-field">RCCM</label>
                <input type="text" wire:model="rccm" class="mt-1 w-full input-field" @disabled($lecture)>
            </div>
            <div>
                <label class="label-field">IFU</label>
                <input type="text" wire:model="ifu" class="mt-1 w-full input-field" @disabled($lecture)>
            </div>
            <div>
                <label class="label-field">Signataire (nom affiché sur les factures)</label>
                <input type="text" wire:model="signataire" class="mt-1 w-full input-field" @disabled($lecture)>
            </div>

            <div class="sm:col-span-2 pt-2">
                <h3 class="font-semibold text-slate-700 text-sm mb-1">Répartition des recettes (doit totaliser 100%)</h3>
                <p class="text-xs text-slate-500">La somme des 4 taux ci-dessous doit toujours faire 100.</p>
            </div>
            <div>
                <label class="label-field">Taux investissement (%)</label>
                <input type="number" step="0.01" wire:model="taux_investissement" class="mt-1 w-full input-field" @disabled($lecture)>
                @error('taux_investissement') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-field">Taux épargne (%)</label>
                <input type="number" step="0.01" wire:model="taux_epargne" class="mt-1 w-full input-field" @disabled($lecture)>
                @error('taux_epargne') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-field">Taux loyer (%)</label>
                <input type="number" step="0.01" wire:model="taux_loyer" class="mt-1 w-full input-field" @disabled($lecture)>
                @error('taux_loyer') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-field">Taux fonctionnement (%)</label>
                <input type="number" step="0.01" wire:model="taux_fonctionnement" class="mt-1 w-full input-field" @disabled($lecture)>
                @error('taux_fonctionnement') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2 pt-2">
                <h3 class="font-semibold text-slate-700 text-sm mb-1">Soldes initiaux</h3>
            </div>
            <div>
                <label class="label-field">Solde initial caisse</label>
                <input type="number" step="0.01" wire:model="solde_initial_caisse" class="mt-1 w-full input-field" @disabled($lecture)>
            </div>
            <div>
                <label class="label-field">Solde initial banque</label>
                <input type="number" step="0.01" wire:model="solde_initial_banque" class="mt-1 w-full input-field" @disabled($lecture)>
            </div>

            @if (auth()->user()->peutModifier('CONFIGURATION_ENTREPRISE'))
                <div class="sm:col-span-2 flex gap-3 pt-2">
                    <x-btn-submit>Enregistrer</x-btn-submit>
                </div>
            @endif
        </form>
    </div>
</div>
