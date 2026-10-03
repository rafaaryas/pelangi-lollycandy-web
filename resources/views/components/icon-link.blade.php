@props(['icon', 'label', 'variant' => 'neutral'])

<a {{ $attributes->class(['icon-button', 'icon-button-link', 'icon-button-'.$variant])->merge(['aria-label' => $label, 'title' => $label, 'data-tooltip' => $label]) }}>
    <x-icon :name="$icon" size="17" />
</a>
