@props([
    'location',
    'value' => null,
    'selected' => false,
])

<option
    value="{{ $value ?? $location->name }}"
    data-state="{{ $location->state ?? '' }}"
    data-city-name="{{ $location->name }}"
    @if($selected) selected @endif
>{{ $location->name }}</option>
