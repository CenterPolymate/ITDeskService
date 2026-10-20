@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold leading-5 text-gray-900 focus:outline-none transition duration-150 ease-in-out whitespace-nowrap'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out whitespace-nowrap';
$style = ($active ?? false) ? 'border-bottom-color: var(--brand-primary, #4f46e5); color: var(--brand-primary, #4f46e5);' : '';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'style' => $style]) }}>
    {{ $slot }}
</a>
