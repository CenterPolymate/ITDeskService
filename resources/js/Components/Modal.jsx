export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => {},
}) {
    if (!show) return null;

    const close = () => {
        if (closeable) {
            onClose();
        }
    };

    const maxWidthClass = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[maxWidth] || 'sm:max-w-2xl';

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
            <div className="fixed inset-0 transform transition-all" onClick={close}>
                <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" />
            </div>

            <div className={`relative mb-6 bg-white rounded-3xl overflow-hidden shadow-2xl transform transition-all sm:w-full ${maxWidthClass} z-10`}>
                {children}
            </div>
        </div>
    );
}
