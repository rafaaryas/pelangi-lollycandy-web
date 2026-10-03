@props(['name'])

@if($errors->has($name))
    <span class="field-error" id="error-{{ str_replace(['.', '[', ']'], '-', $name) }}" role="alert">{{ $errors->first($name) }}</span>
@endif
