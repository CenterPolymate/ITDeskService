const PersonIcon = () => (
    <svg className="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
    </svg>
);

const LocationIcon = () => (
    <svg className="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
);

/**
 * Context-based ticket metadata (Issue #3).
 * Groups people info (requester + reported on behalf of) and location into separate,
 * single-line, truncated rows with hover tooltips so table rows keep a predictable height.
 *
 * layout="stacked" renders each group on its own line; layout="inline" places them side by side.
 */
export default function TicketContextMeta({
    requesterName = null,
    department = null,
    actualUserName = null,
    location = null,
    showRequester = true,
    layout = 'stacked',
    className = '',
}) {
    const hasPeople = showRequester || Boolean(actualUserName);

    if (!hasPeople && !location) {
        return null;
    }

    const requesterText = `${requesterName || '-'} (${department || '-'})`;
    const containerClass = layout === 'inline'
        ? 'flex items-center gap-3'
        : 'flex flex-col gap-0.5';

    return (
        <div className={`${containerClass} min-w-0 text-xs font-normal ${className}`}>
            {hasPeople && (
                <div className="flex items-center gap-1.5 min-w-0 text-gray-500">
                    <PersonIcon />
                    {showRequester && (
                        <span className="truncate" title={requesterText}>
                            {requesterText}
                        </span>
                    )}
                    {actualUserName && (
                        <span
                            className="truncate text-purple-600 font-medium"
                            title={`แจ้งแทน: ${actualUserName}`}
                        >
                            {showRequester ? '↳ ' : ''}แจ้งแทน {actualUserName}
                        </span>
                    )}
                </div>
            )}
            {location && (
                <div className="flex items-center gap-1.5 min-w-0 text-slate-500">
                    <LocationIcon />
                    <span className="truncate" title={location}>
                        {location}
                    </span>
                </div>
            )}
        </div>
    );
}
