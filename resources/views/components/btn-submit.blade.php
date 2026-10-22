@props(['target' => 'enregistrer', 'variant' => 'btn-success'])
<button type="submit" wire:loading.attr="disabled" wire:target="{{ $target }}"
        {{ $attributes->merge(['class' => $variant.' disabled:cursor-not-allowed disabled:opacity-60']) }}>
    <svg wire:loading wire:target="{{ $target }}" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
        <path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9V0a12 12 0 0 1 12 12h-3Z"></path>
    </svg>
    <span wire:loading.remove wire:target="{{ $target }}">{{ $slot }}</span>
    <span wire:loading wire:target="{{ $target }}">Enregistrement...</span>
</button>
