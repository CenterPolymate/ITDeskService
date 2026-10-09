@props(['field', 'label'])
@php
    $currentSort = request('sort');
    $currentDirection = request('direction', 'desc');
    
    $isCurrentField = $currentSort === $field;
    // Default to 'asc' on first click, otherwise toggle
    $newDirection = $isCurrentField && $currentDirection === 'asc' ? 'desc' : 'asc';
@endphp
<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
    <a href="{{ request()->fullUrlWithQuery(['sort' => $field, 'direction' => $newDirection]) }}" class="group flex items-center gap-1 hover:text-gray-700 transition-colors">
        {{ $label }}
        @if($isCurrentField)
            @if($currentDirection === 'asc')
                <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
            @else
                <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            @endif
        @else
            <svg class="w-3 h-3 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
        @endif
    </a>
</th>
