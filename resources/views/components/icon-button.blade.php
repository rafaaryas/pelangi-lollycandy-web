@props(['icon', 'label', 'variant' => 'neutral', 'type' => 'button'])

@php($dialogId = $attributes->get('data-modal-open'))
<button type="{{ $type }}" {{ $attributes->class(['icon-button', 'icon-button-'.$variant])->merge(['aria-label' => $label, 'title' => $label, 'data-tooltip' => $label]) }} @if($dialogId) aria-haspopup="dialog" aria-controls="{{ $dialogId }}" @endif>
    <x-icon :name="$icon" size="17" />
</button>
