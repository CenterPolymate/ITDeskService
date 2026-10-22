import React, { useState, useEffect, useRef } from 'react';
import { markTicketAsViewed } from '@/Utils/webNotification';

/**
 * InAppToastContainer
 * Listens to 'itdesk:toast' window events and renders modern, actionable floating toast alerts.
 */
export default function InAppToastContainer() {
    const [toasts, setToasts] = useState([]);

    useEffect(() => {
        const handleToastEvent = (e) => {
            const detail = e.detail;
            if (!detail) return;

            const id = detail.id || `toast-${Date.now()}-${Math.random().toString(36).substr(2, 5)}`;
            const duration = detail.duration || 8000;

            const newToast = {
                id,
                title: detail.title || 'การแจ้งเตือน',
                body: detail.body || '',
                tag: detail.tag || null,
                url: detail.url || null,
                onClick: detail.onClick || null,
                priority: detail.priority || null,
                type: detail.type || 'info', // 'urgent', 'new', 'chat', 'resolved', 'warning', 'info'
                ticket_no: detail.ticket_no || null,
                ticket_id: detail.ticket_id || null,
                createdAt: Date.now(),
                duration,
                remainingTime: duration,
                isPaused: false,
            };

            setToasts((prev) => {
                // If toast with same tag already exists, replace it
                const filtered = detail.tag ? prev.filter((t) => t.tag !== detail.tag) : prev;
                // Keep max 4 toasts at a time
                return [newToast, ...filtered].slice(0, 4);
            });
        };

        const handleDismissEvent = (e) => {
            const { ticketId, ticket_no } = e.detail || {};
            if (ticketId || ticket_no) {
                setToasts((prev) =>
                    prev.filter((t) => {
                        if (ticketId && (String(t.ticket_id) === String(ticketId) || t.tag === `staff-ticket-new-${ticketId}`)) {
                            return false;
                        }
                        if (ticket_no && t.ticket_no === ticket_no) {
                            return false;
                        }
                        return true;
                    })
                );
            }
        };

        window.addEventListener('itdesk:toast', handleToastEvent);
        window.addEventListener('itdesk:ticket-viewed', handleDismissEvent);
        window.addEventListener('itdesk:dismiss-toast', handleDismissEvent);
        return () => {
            window.removeEventListener('itdesk:toast', handleToastEvent);
            window.removeEventListener('itdesk:ticket-viewed', handleDismissEvent);
            window.removeEventListener('itdesk:dismiss-toast', handleDismissEvent);
        };
    }, []);

    const dismissToast = (id, ticketId = null) => {
        if (ticketId) {
            markTicketAsViewed(ticketId);
        }
        setToasts((prev) => prev.filter((t) => t.id !== id));
    };

    const handleAction = (toast) => {
        dismissToast(toast.id, toast.ticket_id);
        if (toast.ticket_id) {
            markTicketAsViewed(toast.ticket_id);
        }
        if (typeof toast.onClick === 'function') {
            toast.onClick();
        } else if (toast.url) {
            window.location.href = toast.url;
        }
    };

    if (toasts.length === 0) return null;

    return (
        <aside
            aria-label="การแจ้งเตือนในระบบ"
            className="fixed top-4 right-4 sm:top-5 sm:right-5 z-[99999] flex flex-col gap-2.5 max-w-md w-[calc(100vw-2rem)] sm:w-[380px] pointer-events-none"
        >
            {toasts.map((toast) => (
                <ToastItem
                    key={toast.id}
                    toast={toast}
                    onDismiss={() => dismissToast(toast.id, toast.ticket_id)}
                    onAction={() => handleAction(toast)}
                />
            ))}
        </aside>
    );
}

function ToastItem({ toast, onDismiss, onAction }) {
    const [progress, setProgress] = useState(100);
    const [isHovered, setIsHovered] = useState(false);
    const startTimeRef = useRef(Date.now());
    const remainingRef = useRef(toast.duration);

    useEffect(() => {
        if (isHovered) return;

        const interval = 50; // update every 50ms
        const timer = setInterval(() => {
            remainingRef.current -= interval;
            const pct = Math.max(0, (remainingRef.current / toast.duration) * 100);
            setProgress(pct);

            if (remainingRef.current <= 0) {
                clearInterval(timer);
                onDismiss();
            }
        }, interval);

        return () => clearInterval(timer);
    }, [isHovered, toast.duration, onDismiss]);

    // Color theme based on type
    const isUrgent = toast.type === 'urgent' || toast.priority === 'urgent';
    const isChat = toast.type === 'chat';
    const isResolved = toast.type === 'resolved';

    let borderClass = 'border-indigo-500/30';
    let bgHeader = 'bg-indigo-50/80 text-indigo-900 border-indigo-100';
    let progressBarClass = 'bg-indigo-600';
    let badgeClass = 'bg-indigo-100 text-indigo-800';
    let badgeText = 'เคสใหม่';

    if (isUrgent) {
        borderClass = 'border-red-500/50 ring-2 ring-red-400/30 shadow-red-500/10';
        bgHeader = 'bg-red-50/90 text-red-900 border-red-100';
        progressBarClass = 'bg-red-600';
        badgeClass = 'bg-red-600 text-white font-black animate-pulse';
        badgeText = '🚨 ด่วนที่สุด (Urgent)';
    } else if (isChat) {
        borderClass = 'border-sky-500/40';
        bgHeader = 'bg-sky-50/80 text-sky-900 border-sky-100';
        progressBarClass = 'bg-sky-600';
        badgeClass = 'bg-sky-100 text-sky-800';
        badgeText = '💬 แชทใหม่';
    } else if (isResolved) {
        borderClass = 'border-emerald-500/40';
        bgHeader = 'bg-emerald-50/80 text-emerald-900 border-emerald-100';
        progressBarClass = 'bg-emerald-600';
        badgeClass = 'bg-emerald-100 text-emerald-800';
        badgeText = '🎉 ส่งมอบงาน';
    }

    return (
        <div
            onMouseEnter={() => setIsHovered(true)}
            onMouseLeave={() => setIsHovered(false)}
            className={`pointer-events-auto bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border ${borderClass} overflow-hidden transform transition-all duration-300 ease-out animate-slide-in-right flex flex-col`}
            role="alert"
        >
            {/* Header bar */}
            <div className={`px-3.5 py-2 border-b flex items-center justify-between gap-2 ${bgHeader}`}>
                <div className="flex items-center gap-1.5 min-w-0">
                    <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider shrink-0 ${badgeClass}`}>
                        {badgeText}
                    </span>
                    <span className="text-[11px] text-gray-500 truncate">เมื่อสักครู่</span>
                </div>
                <button
                    type="button"
                    onClick={onDismiss}
                    className="text-gray-400 hover:text-gray-700 transition p-1 rounded-lg hover:bg-black/5 cursor-pointer shrink-0"
                    title="ปิดการแจ้งเตือน"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {/* Content body */}
            <div className="p-3.5 space-y-2">
                <h4 className="text-sm font-bold text-gray-900 leading-snug line-clamp-2">
                    {toast.title}
                </h4>

                {toast.body && (
                    <p className="text-xs text-gray-600 leading-relaxed whitespace-pre-line line-clamp-3 bg-gray-50/70 p-2 rounded-xl border border-gray-100 font-normal">
                        {toast.body}
                    </p>
                )}

                {/* Action CTA button */}
                <div className="pt-1 flex items-center justify-end gap-2">
                    {(toast.onClick || toast.url) && (
                        <button
                            type="button"
                            onClick={onAction}
                            className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white shadow-xs transition active:scale-95 cursor-pointer ${
                                isUrgent
                                    ? 'bg-red-600 hover:bg-red-700'
                                    : 'bg-indigo-600 hover:bg-indigo-700'
                            }`}
                        >
                            <span>เปิดดูเคสนี้</span>
                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    )}
                </div>
            </div>

            {/* Progress bar countdown */}
            <div className="h-1 w-full bg-gray-100 overflow-hidden">
                <div
                    className={`h-full transition-all duration-75 ease-linear ${progressBarClass}`}
                    style={{ width: `${progress}%` }}
                />
            </div>
        </div>
    );
}
