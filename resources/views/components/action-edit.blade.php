@props(['call'])
<button type="button" wire:click="{{ $call }}" wire:loading.attr="disabled" wire:target="{{ $call }}"
        class="link-edit mr-3 inline-flex items-center gap-1 disabled:cursor-not-allowed disabled:opacity-50">
    <x-nav-icon name="pencil" class="h-3.5 w-3.5" />
    <span>Modifier</span>
</button>
