import React from 'react';

/**
 * SortableHeader Component
 * A reusable table header cell that supports interactive sorting with visual indicators.
 *
 * @param {string} field - Unique identifier for the sortable column (e.g. 'ticket_no', 'created_at')
 * @param {string} currentSort - Currently active sort field
 * @param {string} currentDirection - 'asc' | 'desc'
 * @param {function} onSort - Callback triggered when header is clicked: (field) => void
 * @param {string} label - Primary Thai label
 * @param {string} subLabel - Optional English / secondary sublabel
 * @param {string} align - 'left' | 'center' | 'right' (default 'left')
 * @param {string} className - Additional CSS classes
 */
export default function SortableHeader({
    field,
    currentSort,
    currentDirection = 'desc',
    onSort,
    label,
    subLabel = '',
    align = 'left',
    className = '',
}) {
    const isActive = currentSort === field;
    const isAsc = isActive && currentDirection === 'asc';
    const isDesc = isActive && currentDirection === 'desc';

    const alignClasses = {
        left: 'justify-start text-left',
        center: 'justify-center text-center',
        right: 'justify-end text-right',
    }[align] || 'justify-start text-left';

    const thAlignClasses = {
        left: 'text-left',
        center: 'text-center',
        right: 'text-right',
    }[align] || 'text-left';

    const ariaSortValue = isActive
        ? isAsc ? 'ascending' : 'descending'
        : 'none';

    const hasCustomPadding = className.includes('px-') || className.includes('p-');
    const defaultPadding = hasCustomPadding ? '' : 'px-4 py-3';

    return (
        <th
            scope="col"
            aria-sort={ariaSortValue}
            className={`${defaultPadding} text-xs font-bold uppercase tracking-wider select-none ${thAlignClasses} ${className}`}
        >
            <button
                type="button"
                onClick={() => onSort(field)}
                title={`คลิกเพื่อเรียงตาม ${label}`}
                className={`group inline-flex items-center gap-1.5 focus:outline-hidden transition-all duration-150 py-1 px-2 -mx-2 rounded-lg ${
                    alignClasses
                } ${
                    isActive
                        ? 'text-indigo-700 bg-indigo-50/70 font-black ring-1 ring-indigo-200'
                        : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100/70 font-semibold'
                }`}
            >
                <span className={`flex flex-col leading-tight ${align === 'center' ? 'items-center text-center' : align === 'right' ? 'items-end text-right' : 'items-start text-left'}`}>
                    <span>{label}</span>
                    {subLabel && (
                        <span className="text-[10px] font-normal tracking-normal text-gray-400 group-hover:text-gray-500">
                            {subLabel}
                        </span>
                    )}
                </span>

                {/* Sort Direction Indicator */}
                <span
                    className={`inline-flex items-center justify-center w-4 h-4 rounded transition-transform ${
                        isActive ? 'text-indigo-600 scale-110' : 'text-gray-300 group-hover:text-gray-500'
                    }`}
                >
                    {isActive ? (
                        isAsc ? (
                            // Ascending Arrow (Up)
                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 15l7-7 7 7" />
                            </svg>
                        ) : (
                            // Descending Arrow (Down)
                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        )
                    ) : (
                        // Neutral Inactive Arrows (Both Up & Down)
                        <svg className="w-3.5 h-3.5 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                        </svg>
                    )}
                </span>
            </button>
        </th>
    );
}
