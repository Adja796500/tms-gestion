@props(['call', 'label' => 'cet element'])
@php($methode = \Illuminate\Support\Str::before($call, '('))
<div x-data="{ open: false }" class="inline-block">
    <button type="button" @click="open = true"
            class="link-danger inline-flex items-center gap-1">
        <x-nav-icon name="trash" class="h-3.5 w-3.5" />
        <span>Supprimer</span>
    </button>

    <div x-show="open" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
         @keydown.escape.window="open = false">
        <div x-show="open" x-transition @click.outside="open = false"
             class="max-h-[85vh] w-full max-w-sm overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-3 flex items-center gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <x-nav-icon name="alert" class="h-5 w-5" />
                </div>
                <h3 class="font-semibold text-slate-900">Confirmer la suppression</h3>
            </div>
            <p class="mb-5 break-words text-sm text-slate-500">
                Voulez-vous vraiment supprimer {{ $label }} ? Cette action est irréversible.
            </p>
            <div class="flex justify-end gap-2">
                <button type="button" @click="open = false" class="btn-ghost">Annuler</button>
                <button type="button" wire:loading.attr="disabled" wire:target="{{ $methode }}"
                        @click="open = false; $wire.{{ $call }}"
                        class="btn-danger disabled:cursor-not-allowed disabled:opacity-60">
                    <x-nav-icon name="trash" class="h-4 w-4" />
                    Supprimer
                </button>
            </div>
        </div>
    </div>
</div>
