<div class="max-w-md">
    <div class="card p-5">
        <h2 class="font-semibold text-slate-800 mb-4">Changer mon mot de passe</h2>
        <form wire:submit="enregistrer" class="space-y-4">
            <div>
                <label class="label-field">Mot de passe actuel</label>
                <input type="password" wire:model="actuel" autocomplete="current-password" class="mt-1 w-full input-field">
                @error('actuel') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-field">Nouveau mot de passe (8 caractères minimum)</label>
                <input type="password" wire:model="nouveau" autocomplete="new-password" class="mt-1 w-full input-field">
                @error('nouveau') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-field">Confirmez le nouveau mot de passe</label>
                <input type="password" wire:model="nouveau_confirmation" autocomplete="new-password" class="mt-1 w-full input-field">
            </div>
            <x-btn-submit>Enregistrer</x-btn-submit>
        </form>
    </div>
</div>
