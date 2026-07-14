@props([
    'label',
    'name',
    'type' => 'text',
    'autocomplete' => null,
])

@php $hasError = $errors->has($name); @endphp

<label class="flex flex-col gap-1.5 text-sm font-semibold text-ink-muted">
    {{ $label }}

    <input
        type="{{ $type }}"
        id="{{ $name }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->class([
            'rounded-xl border bg-white px-4 py-3.5 font-normal text-ink transition outline-none',
            'focus:border-terracotta focus:ring-3 focus:ring-terracotta/15',
            'border-terracotta' => $hasError,
            'border-earth/20' => ! $hasError,
        ]) }}
    >

    @error($name)
        <span class="text-[13.5px] font-medium text-terracotta">{{ $message }}</span>
    @enderror
</label>
