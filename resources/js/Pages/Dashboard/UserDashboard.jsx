import React, { useState, useEffect, useRef } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { sendBrowserNotification, markTicketAsViewed } from '@/Utils/webNotification';
import Modal from '@/Components/Modal';
import SortableHeader from '@/Components/SortableHeader';

export default function UserDashboard({ cases, stats, active_tickets = [], waiting_tickets = [], filters = {} }) {
    const { auth, flash, branding } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const currentStatus = filters.status || 'all';
    const [highlightedTicketIds, setHighlightedTicketIds] = useState(new Set());
    const [isRefreshing, setIsRefreshing] = useState(false);
    const knownCasesRef = useRef(null);
    const searchTimeoutRef = useRef(null);

    const handleManualRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            only: ['cases', 'stats', 'active_tickets', 'waiting_tickets'],
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                setTimeout(() => setIsRefreshing(false), 600);
            },
        });
    };

    // Cleanup timeout on unmount
    useEffect(() => {
        return () => {
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }
        };
    }, []);

    // Listen to ticket viewed events to immediately clear any highlight
    useEffect(() => {
        const handleTicketViewed = (e) => {
            const viewedId = e.detail?.ticketId;
            if (viewedId) {
                const numId = Number(viewedId);
                setHighlightedTicketIds(prev => {
                    if (!prev.has(viewedId) && !prev.has(numId)) return prev;
                    const next = new Set(prev);
                    next.delete(viewedId);
                    next.delete(numId);
                    return next;
                });
            }
        };
        window.addEventListener('itdesk:ticket-viewed', handleTicketViewed);
        return () => window.removeEventListener('itdesk:ticket-viewed', handleTicketViewed);
    }, []);

    // CSAT 1-Click Acceptance States
    const [showCsatModal, setShowCsatModal] = useState(false);
    const [selectedTicketForCsat, setSelectedTicketForCsat] = useState(null);
    const [csatRating, setCsatRating] = useState(5);
    const [csatHover, setCsatHover] = useState(0);
    const [csatFeedback, setCsatFeedback] = useState('');
    const [isSubmittingCsat, setIsSubmittingCsat] = useState(false);

    const handleOpenCsatModal = (ticket) => {
        setSelectedTicketForCsat(ticket);
        setCsatRating(5);
        setCsatHover(0);
        setCsatFeedback('');
        setShowCsatModal(true);
    };

    const handleSubmitCsat = (e) => {
        e.preventDefault();
        if (!selectedTicketForCsat) return;
        setIsSubmittingCsat(true);
        router.put(
            route('tickets.updateStatus', selectedTicketForCsat.id),
            {
                action: 'accept_resolution',
                rating: csatRating,
                feedback: csatFeedback,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowCsatModal(false);
                    setSelectedTicketForCsat(null);
                    setCsatFeedback('');
                    setCsatRating(5);
                    setIsSubmittingCsat(false);
                },
                onError: () => {
                    setIsSubmittingCsat(false);
                },
            }
        );
    };

    // Watch for ticket status changes and notify
    useEffect(() => {
        const ticketList = cases?.data || (Array.isArray(cases) ? cases : []);

        if (knownCasesRef.current === null) {
            knownCasesRef.current = new Map(ticketList.map(c => [c.id, c.status]));
            return;
        }

        ticketList.forEach(t => {
            const prevStatus = knownCasesRef.current.get(t.id);
            if (prevStatus && prevStatus !== t.status) {
                let title = `📌 ใบงาน #${t.ticket_no} มีการอัปเดต`;
                let body = `สถานะปัจจุบัน: ${t.status}`;

                if (t.status === 'in_progress') {
                    title = `⚙️ ช่างกำลังดำเนินการแก้ไข: #${t.ticket_no}`;
                    body = `${t.title || 'กำลังดำเนินการ'}\nทีมงานกำลังดำเนินการแก้ไขปัญหาของคุณ`;
                } else if (t.status === 'resolved') {
                    title = `🎉 ซ่อมเสร็จสิ้นแล้ว: #${t.ticket_no}`;
                    body = `${t.title || 'ส่งมอบงานแล้ว'}\nกรุณาตรวจสอบและกดยืนยันรับงาน (Accept Resolution)`;
                } else if (t.status === 'closed') {
                    title = `✅ ใบงาน #${t.ticket_no} ปิดเรียบร้อยแล้ว`;
                    body = `${t.title || 'ปิดงานแล้ว'}\nขอบคุณที่ใช้บริการ`;
                } else if (t.status === 'cancelled') {
                    title = `❌ ใบงาน #${t.ticket_no} ถูกยกเลิก`;
                    body = `${t.title || 'ยกเลิก'}`;
                }

                sendBrowserNotification(title, {
                    body,
                    tag: `user-ticket-${t.id}-${t.status}`,
                    url: route('tickets.show', t.id),
                    ticket_no: t.ticket_no,
                    ticket_id: t.id,
                    type: t.status === 'resolved' ? 'resolved' : 'info',
                    onClick: () => {
                        markTicketAsViewed(t.id, auth.user?.id);
                        window.location.href = route('tickets.show', t.id);
                    },
                });

                // Highlight updated ticket for 30s
                setHighlightedTicketIds(prev => new Set(prev).add(t.id));
                setTimeout(() => {
                    setHighlightedTicketIds(prev => {
                        const next = new Set(prev);
                        next.delete(t.id);
                        return next;
                    });
                }, 30000);
            }
            knownCasesRef.current.set(t.id, t.status);
        });
    }, [cases]);

    // Auto-polling updates every 15 seconds
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({
                only: ['cases', 'stats', 'active_tickets', 'waiting_tickets'],
                preserveScroll: true,
                preserveState: true,
            });
        }, 15000);

        return () => clearInterval(interval);
    }, []);

    const formatThaiDate = (dateStr) => {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        const months = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];
        const day = d.getDate();
        const month = months[d.getMonth()];
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        return `${day} ${month} ${year} ${hours}:${minutes}`;
    };

    const currentSort = filters.sort || 'created_at';
    const currentDirection = filters.direction || 'desc';
    const currentPerPage = Number(filters.per_page) || 10;

    const handleSort = (field) => {
        let nextDirection = 'asc';
        if (currentSort === field) {
            nextDirection = currentDirection === 'asc' ? 'desc' : 'asc';
        } else {
            nextDirection = (field === 'created_at' || field === 'ticket_no') ? 'desc' : 'asc';
        }
        handleSortDirect(field, nextDirection);
    };

    const triggerSearch = (query, overridePerPage) => {
        const perPageVal = overridePerPage !== undefined ? overridePerPage : currentPerPage;
        router.get(
            route('dashboard'),
            {
                status: currentStatus !== 'all' ? currentStatus : undefined,
                search: query?.trim() ? query.trim() : undefined,
                sort: currentSort !== 'created_at' ? currentSort : undefined,
                direction: currentDirection !== 'desc' ? currentDirection : undefined,
                per_page: perPageVal !== 10 ? perPageVal : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleSearchChange = (val) => {
        setSearch(val);
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        searchTimeoutRef.current = setTimeout(() => {
            triggerSearch(val);
        }, 350);
    };

    const handleSortDirect = (field, direction) => {
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        router.get(
            route('dashboard'),
            {
                status: currentStatus !== 'all' ? currentStatus : undefined,
                search: search?.trim() ? search.trim() : undefined,
                sort: field,
                direction: direction,
                per_page: currentPerPage !== 10 ? currentPerPage : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleSearchSubmit = (e) => {
        if (e) e.preventDefault();
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        triggerSearch(search);
    };

    const handleClearSearch = () => {
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        setSearch('');
        triggerSearch('');
    };

    const handleFilterStatus = (statusKey) => {
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        setSearch('');
        router.get(
            route('dashboard'),
            {
                status: statusKey !== 'all' ? statusKey : undefined,
                sort: currentSort !== 'created_at' ? currentSort : undefined,
                direction: currentDirection !== 'desc' ? currentDirection : undefined,
                per_page: currentPerPage !== 10 ? currentPerPage : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handlePerPageChange = (newPerPage) => {
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        router.get(
            route('dashboard'),
            {
                status: currentStatus !== 'all' ? currentStatus : undefined,
                search: search?.trim() ? search.trim() : undefined,
                sort: currentSort !== 'created_at' ? currentSort : undefined,
                direction: currentDirection !== 'desc' ? currentDirection : undefined,
                per_page: Number(newPerPage) !== 10 ? Number(newPerPage) : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const getStatusBadge = (status, statusLabel) => {
        const statusMap = {
            pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            assigned: 'bg-purple-100 text-purple-800 border-purple-200',
            analyzing: 'bg-amber-100 text-amber-800 border-amber-200',
            in_progress: 'bg-blue-100 text-blue-800 border-blue-200',
            resolved: 'bg-cyan-100 text-cyan-800 border-cyan-200',
            approved: 'bg-sky-100 text-sky-800 border-sky-200',
            closed: 'bg-emerald-100 text-emerald-800 border-emerald-200',
            cancelled: 'bg-gray-100 text-gray-700 border-gray-200',
        };

        const badgeClass = statusMap[status] || 'bg-gray-100 text-gray-800 border-gray-200';

        return (
            <span className={`px-2.5 py-0.5 inline-flex text-xs font-semibold rounded-full border ${badgeClass}`}>
                {statusLabel || status}
            </span>
        );
    };

    return (
        <>
            <Head title="แดชบอร์ดผู้ใช้งาน - ประวัติการแจ้งซ่อม" />

            <div className="py-4 sm:py-6">
                <div id="dashboard-content" className="w-full px-4 sm:px-6 lg:px-8 mx-auto xl:max-w-[95%] space-y-6">

                    {/* Flash Success */}
                    {flash?.success && (
                        <div className="bg-emerald-50 border border-emerald-400 text-emerald-800 px-4 py-3 rounded-xl shadow-sm flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <svg className="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span className="text-sm font-medium">{flash.success}</span>
                            </div>
                        </div>
                    )}

                    {/* Flash Error */}
                    {flash?.error && (
                        <div className="bg-rose-50 border border-rose-400 text-rose-800 px-4 py-3 rounded-xl shadow-sm flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <svg className="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span className="text-sm font-medium">{flash.error}</span>
                            </div>
                        </div>
                    )}

                    {/* Waiting for Acceptance Banner */}
                    {stats.waiting_acceptance > 0 && (
                        <div className="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 rounded-2xl p-5 sm:p-6 shadow-lg text-white overflow-hidden relative border border-emerald-400">
                            <div className="absolute right-0 top-0 w-64 h-64 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-2xl translate-x-16 -translate-y-16 pointer-events-none" />
                            <div className="absolute left-0 bottom-0 w-40 h-40 bg-yellow-300 opacity-20 rounded-full mix-blend-overlay filter blur-xl -translate-x-10 translate-y-10 pointer-events-none" />

                            <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 z-10 relative">
                                <div className="flex items-start gap-4">
                                    <div className="bg-white/20 p-3 rounded-2xl animate-bounce shrink-0 mt-0.5">
                                        <svg className="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <h3 className="text-lg sm:text-xl font-black tracking-wide">
                                                แจ้งเตือน: มีใบงานที่ซ่อมเสร็จแล้ว รอให้คุณตรวจรับ
                                            </h3>
                                            <span className="px-2.5 py-0.5 bg-yellow-400 text-yellow-950 font-black text-xs rounded-full shadow-sm">
                                                {stats.waiting_acceptance} รายการ
                                            </span>
                                        </div>
                                        <p className="text-emerald-100 text-xs sm:text-sm mt-1">
                                            ทีมงานได้ดำเนินการแก้ไขปัญหาของคุณเสร็จสิ้นแล้ว กรุณาตรวจสอบผลการแก้ไขและกดยืนยันรับงานพร้อมให้คะแนนความพึงพอใจ
                                        </p>

                                        {/* Direct 1-Click Ticket Buttons */}
                                        {waiting_tickets && waiting_tickets.length > 0 && (
                                            <div className="mt-3 flex flex-wrap gap-2">
                                                {waiting_tickets.map((wt) => (
                                                    <button
                                                        key={wt.id}
                                                        type="button"
                                                        onClick={() => handleOpenCsatModal(wt)}
                                                        className="bg-white/15 hover:bg-white/25 border border-white/30 text-white rounded-xl px-3 py-1.5 text-xs font-semibold flex items-center gap-2 transition hover:scale-105 cursor-pointer"
                                                    >
                                                        <span className="font-mono font-bold text-yellow-300">#{wt.ticket_no}</span>
                                                        <span className="truncate max-w-[180px] sm:max-w-[260px]">{wt.title}</span>
                                                        <span className="bg-white text-emerald-800 font-bold px-2 py-0.5 rounded-lg text-[10px] shrink-0 shadow-xs">
                                                            ⭐ กดยืนยันรับงาน
                                                        </span>
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="w-full lg:w-auto flex flex-col sm:flex-row gap-2 shrink-0">
                                    {waiting_tickets && waiting_tickets.length === 1 && (
                                        <button
                                            type="button"
                                            onClick={() => handleOpenCsatModal(waiting_tickets[0])}
                                            className="w-full sm:w-auto bg-yellow-400 hover:bg-yellow-300 text-yellow-950 font-black py-2.5 px-6 rounded-xl shadow-md hover:shadow-lg transform transition hover:-translate-y-0.5 text-sm flex items-center justify-center gap-2 cursor-pointer"
                                        >
                                            <span>⭐ กดยืนยันรับงานทันที</span>
                                        </button>
                                    )}
                                    <button
                                        type="button"
                                        onClick={() => handleFilterStatus('resolved')}
                                        className="w-full sm:w-auto bg-white/20 hover:bg-white/30 text-white font-bold py-2.5 px-5 rounded-xl border border-white/30 text-sm transition text-center cursor-pointer"
                                    >
                                        ดูรายการทั้งหมด ({stats.waiting_acceptance})
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Active Ticket Spotlight (⚡ เคสที่กำลังดำเนินการล่าสุด - ไม่แสดงข้อมูลช่าง) */}
                    {active_tickets && active_tickets.length > 0 && (
                        <div className="bg-gradient-to-br from-indigo-900 via-slate-900 to-blue-950 rounded-2xl p-5 sm:p-6 text-white shadow-xl relative overflow-hidden border border-indigo-500/30">
                            <div className="absolute right-0 top-0 w-80 h-80 bg-blue-500 opacity-10 rounded-full blur-3xl pointer-events-none" />

                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 relative z-10 border-b border-white/10 pb-3">
                                <div className="flex items-center gap-2.5">
                                    <span className="flex h-3 w-3 relative">
                                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                        <span className="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                                    </span>
                                    <h3 className="text-base sm:text-lg font-bold tracking-wide">
                                        เคสที่กำลังดำเนินการอยู่ในขณะนี้ (Active Tickets)
                                    </h3>
                                </div>
                                <span className="text-xs text-blue-200 bg-white/10 px-3 py-1 rounded-full font-medium self-start sm:self-auto">
                                    ทีมงานกำลังดูแลเคสของคุณอยู่ {active_tickets.length} รายการ
                                </span>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5 relative z-10">
                                {active_tickets.map((t) => (
                                    <div
                                        key={t.id}
                                        className="bg-white/10 hover:bg-white/15 border border-white/15 rounded-xl p-4 transition-all duration-200 flex flex-col justify-between backdrop-blur-sm group"
                                    >
                                        <div>
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="text-xs font-mono font-bold text-blue-300">
                                                    #{t.ticket_no}
                                                </span>
                                                {t.status === 'in_progress' ? (
                                                    <span className="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-500/20 text-blue-300 border border-blue-400/40 flex items-center gap-1.5">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse" />
                                                        กำลังดำเนินการแก้ไข
                                                    </span>
                                                ) : (
                                                    <span className="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-500/20 text-purple-300 border border-purple-400/40 flex items-center gap-1.5">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-purple-400 animate-pulse" />
                                                        กำลังสืบสภาพปัญหา
                                                    </span>
                                                )}
                                            </div>
                                            <h4 className="text-sm font-bold text-white line-clamp-2 mb-2 group-hover:text-blue-200 transition-colors">
                                                {t.title}
                                            </h4>
                                            <div className="text-xs text-slate-300 flex items-center gap-1.5">
                                                <svg className="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                                <span className="truncate">{t.category || 'อื่นๆ'}</span>
                                            </div>
                                        </div>
                                        <div className="mt-4 pt-3 border-t border-white/10 flex items-center justify-between">
                                            <span className="text-[11px] text-slate-400">
                                                {formatThaiDate(t.created_at)}
                                            </span>
                                            <a
                                                href={route('tickets.show', t.id)}
                                                className="text-xs text-blue-300 hover:text-white font-semibold flex items-center gap-1 hover:underline cursor-pointer"
                                            >
                                                ดูความคืบหน้า →
                                            </a>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Welcome Banner & New Ticket Action */}
                    <div className="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                        <div className="p-5 sm:p-6 text-gray-900 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span>สวัสดี, {auth?.user?.name}</span>
                                    <span className="inline-block animate-wave text-xl">👋</span>
                                </h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    ยินดีต้อนรับสู่ระบบแจ้งซ่อม {branding?.app_name || 'ITDeskService'} คุณสามารถติดตามสถานะการแจ้งซ่อม หรือเปิดใบงานใหม่ได้ที่นี่
                                </p>
                            </div>
                            <div className="w-full sm:w-auto shrink-0">
                                <a
                                    href={route('tickets.create')}
                                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 transition shadow-sm hover:shadow-md"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    เปิดใบงานใหม่ (New Ticket)
                                </a>
                            </div>
                        </div>
                    </div>

                    {/* KPI Cards */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {/* Total Tickets */}
                        <button
                            type="button"
                            onClick={() => handleFilterStatus('all')}
                            className={`bg-white shadow-sm rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 transition-all text-left h-32 hover:shadow-md ${
                                currentStatus === 'all'
                                    ? 'border-gray-900 ring-2 ring-gray-900/10'
                                    : 'border-gray-200 hover:border-gray-300'
                            }`}
                        >
                            <div className="absolute right-0 top-0 w-24 h-24 bg-gray-100 rounded-full mix-blend-multiply opacity-50 translate-x-8 -translate-y-8 pointer-events-none" />
                            <p className="text-sm font-bold text-gray-800 mb-2 leading-tight">ใบงานทั้งหมด</p>
                            <div className="flex items-end justify-between mt-auto z-10 w-full">
                                <span className="text-3xl sm:text-4xl font-black text-gray-900 leading-none drop-shadow-sm">
                                    {stats.total.toLocaleString()}
                                </span>
                                <svg className="w-6 h-6 sm:w-7 sm:h-7 text-gray-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                        </button>

                        {/* Pending Tickets */}
                        <button
                            type="button"
                            onClick={() => handleFilterStatus('user_pending')}
                            className={`bg-white shadow-sm rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 transition-all text-left h-32 hover:shadow-md ${
                                currentStatus === 'user_pending'
                                    ? 'border-yellow-500 ring-2 ring-yellow-500/20'
                                    : 'border-yellow-200 hover:border-yellow-300'
                            }`}
                        >
                            <div className="absolute right-0 top-0 w-24 h-24 bg-yellow-100 rounded-full mix-blend-multiply opacity-50 translate-x-8 -translate-y-8 pointer-events-none" />
                            <p className="text-sm font-bold text-yellow-800 mb-2 leading-tight">เปิดเคส / รอดำเนินการ</p>
                            <div className="flex items-end justify-between mt-auto z-10 w-full">
                                <span className="text-3xl sm:text-4xl font-black text-yellow-600 leading-none drop-shadow-sm">
                                    {stats.pending.toLocaleString()}
                                </span>
                                <svg className="w-6 h-6 sm:w-7 sm:h-7 text-yellow-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </button>

                        {/* In Progress Tickets */}
                        <button
                            type="button"
                            onClick={() => handleFilterStatus('user_in_progress')}
                            className={`bg-white shadow-sm rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 transition-all text-left h-32 hover:shadow-md ${
                                currentStatus === 'user_in_progress'
                                    ? 'border-blue-500 ring-2 ring-blue-500/20'
                                    : 'border-blue-200 hover:border-blue-300'
                            }`}
                        >
                            <div className="absolute right-0 top-0 w-24 h-24 bg-blue-100 rounded-full mix-blend-multiply opacity-50 translate-x-8 -translate-y-8 pointer-events-none" />
                            <p className="text-sm font-bold text-blue-800 mb-2 leading-tight">กำลังดำเนินการ</p>
                            <div className="flex items-end justify-between mt-auto z-10 w-full">
                                <span className="text-3xl sm:text-4xl font-black text-blue-600 leading-none drop-shadow-sm">
                                    {stats.in_progress.toLocaleString()}
                                </span>
                                <svg className="w-6 h-6 sm:w-7 sm:h-7 text-blue-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                        </button>

                        {/* Completed Tickets */}
                        <button
                            type="button"
                            onClick={() => handleFilterStatus('user_completed')}
                            className={`bg-white shadow-sm rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 transition-all text-left h-32 hover:shadow-md ${
                                currentStatus === 'user_completed'
                                    ? 'border-emerald-500 ring-2 ring-emerald-500/20'
                                    : 'border-green-200 hover:border-green-300'
                            }`}
                        >
                            <div className="absolute right-0 top-0 w-24 h-24 bg-green-100 rounded-full mix-blend-multiply opacity-50 translate-x-8 -translate-y-8 pointer-events-none" />
                            <p className="text-sm font-bold text-green-800 mb-2 leading-tight">แก้ไขเสร็จ / ปิดใบงาน</p>
                            <div className="flex items-end justify-between mt-auto z-10 w-full">
                                <span className="text-3xl sm:text-4xl font-black text-green-600 leading-none drop-shadow-sm">
                                    {stats.completed.toLocaleString()}
                                </span>
                                <svg className="w-6 h-6 sm:w-7 sm:h-7 text-green-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </button>
                    </div>

                    {/* Ticket List Section */}
                    <div className="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                        <div className="p-5 sm:p-6 text-gray-900">
                            {/* Header & Search */}
                            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                                <div className="flex items-center gap-3 flex-wrap">
                                    <h3 className="text-lg font-bold text-gray-900">ประวัติการแจ้งซ่อมของคุณ</h3>

                                    {/* Live Update Indicator & Manual Refresh */}
                                    <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-700 shadow-2xs">
                                        <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>อัปเดตอัตโนมัติ</span>
                                        <button
                                            type="button"
                                            onClick={handleManualRefresh}
                                            title="กดเพื่อรีเฟรชข้อมูลล่าสุด"
                                            className="text-emerald-700 hover:text-emerald-900 p-0.5 rounded transition hover:bg-emerald-100 cursor-pointer"
                                        >
                                            <svg className={`w-3.5 h-3.5 ${isRefreshing ? 'animate-spin' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>

                                    {currentStatus !== 'all' && (
                                        <button
                                            type="button"
                                            onClick={() => handleFilterStatus('all')}
                                            className="text-xs text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-full transition flex items-center gap-1 font-medium cursor-pointer"
                                        >
                                            <span>ล้างตัวกรองสถานะ</span>
                                            <span>✕</span>
                                        </button>
                                    )}
                                </div>

                                <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                                    {/* Per Page Selector */}
                                    <div className="flex items-center gap-2 text-xs text-gray-500 justify-end">
                                        <span className="whitespace-nowrap">แสดง:</span>
                                        <select
                                            value={currentPerPage}
                                            onChange={(e) => handlePerPageChange(Number(e.target.value))}
                                            aria-label="เลือกจำนวนรายการต่อหน้า"
                                            className="text-xs border-gray-300 rounded-lg py-1.5 pl-3 pr-8 bg-white text-gray-700 focus:ring-blue-500 focus:border-blue-500 shadow-2xs cursor-pointer"
                                        >
                                            <option value={10}>10 รายการ</option>
                                            <option value={25}>25 รายการ</option>
                                            <option value={50}>50 รายการ</option>
                                        </select>
                                    </div>

                                    <form onSubmit={handleSearchSubmit} className="w-full sm:w-80">
                                        <div className="relative">
                                            <input
                                                type="text"
                                                value={search}
                                                onChange={(e) => handleSearchChange(e.target.value)}
                                                placeholder="ค้นหาใบงาน, หัวข้อปัญหา, สถานที่..."
                                                className="w-full pl-10 pr-9 py-2 border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 shadow-sm text-sm leading-normal"
                                            />
                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg className="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </div>
                                            {search && (
                                                <button
                                                    type="button"
                                                    onClick={handleClearSearch}
                                                    className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                                >
                                                    ✕
                                                </button>
                                            )}
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {/* Ticket Content */}
                            {!cases.data || cases.data.length === 0 ? (
                                <div className="text-center py-12 flex flex-col items-center">
                                    <img
                                        src="/images/empty-state.jpg"
                                        alt="Empty State"
                                        className="w-44 h-44 object-contain mb-4 opacity-80 rounded-2xl shadow-sm mix-blend-multiply"
                                        onError={(e) => {
                                            e.currentTarget.style.display = 'none';
                                        }}
                                    />
                                    <h3 className="mt-2 text-lg font-bold text-gray-900">ยังไม่มีใบงานในขณะนี้</h3>
                                    <p className="mt-1 text-sm text-gray-500 max-w-sm">
                                        {search
                                            ? `ไม่พบใบงานที่ตรงกับคำค้นหา "${search}"`
                                            : 'หน้ากระดานของคุณว่างเปล่า คุณสามารถเปิดใบงานแจ้งปัญหาได้ทันทีที่พบปัญหา'}
                                    </p>
                                    <a
                                        href={route('tickets.create')}
                                        className="mt-6 inline-flex items-center gap-2 px-5 py-2.5 border border-transparent shadow-sm text-sm font-semibold rounded-xl text-white bg-blue-600 hover:bg-blue-700 transition"
                                    >
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        เปิดใบงานใหม่
                                    </a>
                                </div>
                            ) : (
                                <>
                                    {/* Mobile Sort Bar */}
                                    <div className="md:hidden flex items-center justify-between gap-2 pb-3 mb-2 border-b border-gray-100">
                                        <div className="flex items-center gap-1.5 text-xs font-semibold text-gray-500">
                                            <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                                            </svg>
                                            <span>เรียงตาม:</span>
                                        </div>
                                        <div className="flex items-center gap-1.5">
                                            <select
                                                value={currentSort}
                                                onChange={(e) => handleSortDirect(e.target.value, currentDirection)}
                                                aria-label="เลือกฟิลด์สำหรับเรียงลำดับ"
                                                className="text-xs font-medium border-gray-200 rounded-lg py-1 pl-2.5 pr-8 bg-white text-gray-700 focus:ring-blue-500 focus:border-blue-500 shadow-2xs cursor-pointer"
                                            >
                                                <option value="created_at">วันที่แจ้งล่าสุด (Default)</option>
                                                <option value="ticket_no">รหัสใบงาน (Ticket ID)</option>
                                                <option value="status">สถานะงาน (Status)</option>
                                                <option value="title">หัวข้อปัญหา (Subject)</option>
                                            </select>
                                            <button
                                                type="button"
                                                onClick={() => handleSortDirect(currentSort, currentDirection === 'asc' ? 'desc' : 'asc')}
                                                title={currentDirection === 'asc' ? 'เรียงจากน้อยไปมาก (เก่า-ใหม่)' : 'เรียงจากมากไปน้อย (ใหม่-เก่า)'}
                                                aria-label="สลับทิศทางการเรียงลำดับ"
                                                className="p-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-100 text-gray-600 focus:ring-1 focus:ring-blue-500 transition-colors shadow-2xs"
                                            >
                                                {currentDirection === 'asc' ? (
                                                    <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4" />
                                                    </svg>
                                                ) : (
                                                    <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                                                    </svg>
                                                )}
                                            </button>
                                        </div>
                                    </div>

                                    {/* Mobile Cards View (md:hidden) */}
                                    <div className="md:hidden space-y-3">
                                        {cases.data.map((c) => {
                                            const isHighlighted = highlightedTicketIds.has(c.id);
                                            return (
                                                <a
                                                    key={c.id}
                                                    href={route('tickets.show', c.id)}
                                                    className={`block rounded-xl shadow-sm transition p-4 ${
                                                        isHighlighted
                                                            ? 'bg-amber-50/90 border-2 border-amber-400 ring-2 ring-amber-300 shadow-md animate-pulse'
                                                            : 'bg-white border border-gray-200 hover:shadow-md active:bg-gray-50'
                                                    }`}
                                                >
                                                    <div className="flex justify-between items-start mb-2">
                                                        <div className="flex items-center gap-1.5 flex-wrap">
                                                            <span className="text-sm font-bold text-blue-600">
                                                                #{c.ticket_no}
                                                            </span>
                                                            {isHighlighted && (
                                                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white shadow-xs">
                                                                    <span className="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                                                    อัปเดตล่าสุด
                                                                </span>
                                                            )}
                                                        </div>
                                                        {getStatusBadge(c.status, c.status_label)}
                                                    </div>

                                                    <h4 className="text-base font-semibold text-gray-900 mb-1 leading-tight">
                                                        {c.title}
                                                    </h4>
                                                {c.location && (
                                                    <div className="flex items-center gap-1 text-xs text-slate-500 mb-2">
                                                        <span>📍</span>
                                                        <span className="truncate">{c.location}</span>
                                                    </div>
                                                )}
                                                {c.actual_user_name && (
                                                    <div className="mb-2">
                                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                            <svg className="w-3 h-3 text-purple-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                            แจ้งแทน: {c.actual_user_name}
                                                        </span>
                                                    </div>
                                                )}

                                                {c.status === 'cancelled' && c.cancellation_reason && (
                                                    <div className="mb-2 p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-700">
                                                        <div className="flex items-start gap-1.5">
                                                            <span className="text-slate-400 shrink-0">⛔</span>
                                                            <div className="min-w-0">
                                                                <span className="font-semibold text-rose-600">เหตุผลที่ยกเลิก:</span> {c.cancellation_reason}
                                                                {c.cancelled_by?.name && (
                                                                    <span className="text-[11px] text-slate-500 block mt-0.5">โดย {c.cancelled_by.name}</span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                )}

                                                <div className="flex justify-end items-center text-xs text-gray-500 pt-2.5 border-t border-gray-100">
                                                    <span className="flex items-center gap-1 text-[11px]">
                                                        <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span>แจ้งเมื่อ: {formatThaiDate(c.created_at)}</span>
                                                    </span>
                                                </div>

                                                {c.status === 'resolved' && (
                                                    <div className="mt-2.5 pt-2 border-t border-gray-100">
                                                        <button
                                                            type="button"
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                e.stopPropagation();
                                                                handleOpenCsatModal(c);
                                                            }}
                                                            className="w-full py-2 px-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold rounded-xl text-xs shadow-sm flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer"
                                                        >
                                                            <span>⭐ ตรวจรับงานและให้คะแนน (Accept)</span>
                                                        </button>
                                                    </div>
                                                )}

                                                {c.status === 'closed' && c.rating && (
                                                    <div className="mt-2.5 pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
                                                        <span className="text-gray-500 font-medium">คะแนนความพึงพอใจ:</span>
                                                        <span
                                                            className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 font-bold"
                                                            title={c.feedback ? `ข้อเสนอแนะ: ${c.feedback}` : undefined}
                                                        >
                                                            <span>⭐</span>
                                                            <span>{c.rating}/5 ดาว</span>
                                                        </span>
                                                    </div>
                                                )}
                                                </a>
                                            );
                                        })}
                                    </div>

                                    {/* Desktop Table View (hidden md:block) */}
                                    <div className="hidden md:block overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200">
                                            <thead className="bg-gray-50/80 rounded-lg">
                                                <tr>
                                                    <SortableHeader
                                                        field="ticket_no"
                                                        currentSort={currentSort}
                                                        currentDirection={currentDirection}
                                                        onSort={handleSort}
                                                        label="Ticket No"
                                                        subLabel="รหัสใบงาน"
                                                    />
                                                    <SortableHeader
                                                        field="title"
                                                        currentSort={currentSort}
                                                        currentDirection={currentDirection}
                                                        onSort={handleSort}
                                                        label="หัวข้อปัญหา"
                                                        subLabel="Subject"
                                                    />
                                                    <SortableHeader
                                                        field="status"
                                                        currentSort={currentSort}
                                                        currentDirection={currentDirection}
                                                        onSort={handleSort}
                                                        label="สถานะ"
                                                        subLabel="Status"
                                                    />
                                                    <SortableHeader
                                                        field="created_at"
                                                        currentSort={currentSort}
                                                        currentDirection={currentDirection}
                                                        onSort={handleSort}
                                                        label="วันที่แจ้ง"
                                                        subLabel="Reported Date"
                                                    />
                                                    <th scope="col" className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                        จัดการ
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white divide-y divide-gray-100">
                                                {cases.data.map((c) => {
                                                    const isHighlighted = highlightedTicketIds.has(c.id);
                                                    return (
                                                        <tr
                                                            key={c.id}
                                                            onClick={(e) => {
                                                                if (e.target.closest('a, button, select, input, textarea, label')) return;
                                                                router.visit(route('tickets.show', c.id));
                                                            }}
                                                            className={`cursor-pointer transition-all duration-300 ${
                                                                isHighlighted
                                                                    ? 'bg-amber-50/95 ring-2 ring-inset ring-amber-400 shadow-xs'
                                                                    : 'hover:bg-blue-50/60 transition-colors'
                                                            }`}
                                                        >
                                                            <td className="px-5 py-3.5 whitespace-nowrap text-sm font-bold">
                                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                                    <a
                                                                        href={route('tickets.show', c.id)}
                                                                        className="text-blue-600 hover:text-blue-800 hover:underline"
                                                                    >
                                                                        #{c.ticket_no}
                                                                    </a>
                                                                    {isHighlighted && (
                                                                        <span className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white shadow-xs animate-bounce">
                                                                            <span className="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                                                            UPDATE
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </td>
                                                            <td className="px-5 py-3.5 text-sm text-gray-900 font-medium max-w-md">
                                                                <div className="truncate font-semibold">{c.title}</div>
                                                                <div className="flex items-center gap-2 mt-1 flex-wrap">
                                                                    {c.location && (
                                                                        <span className="inline-flex items-center gap-1 text-xs text-slate-500 font-normal">
                                                                            <span>📍</span>
                                                                            <span className="truncate max-w-[200px]">{c.location}</span>
                                                                        </span>
                                                                    )}
                                                                    {c.actual_user_name && (
                                                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                                            <svg className="w-3 h-3 text-purple-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                                            </svg>
                                                                            แจ้งแทน: {c.actual_user_name}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </td>
                                                            <td className="px-5 py-3.5 whitespace-nowrap text-sm">
                                                                <div className="flex flex-col gap-1 items-start">
                                                                    {getStatusBadge(c.status, c.status_label)}
                                                                    {c.status === 'cancelled' && c.cancellation_reason && (
                                                                        <span
                                                                            className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-50 text-slate-600 border border-slate-200 text-[11px] max-w-[200px] truncate"
                                                                            title={`เหตุผลที่ยกเลิก: ${c.cancellation_reason}${c.cancelled_by?.name ? ` (โดย ${c.cancelled_by.name})` : ''}`}
                                                                        >
                                                                            <span className="text-slate-400 shrink-0">⛔</span>
                                                                            <span className="truncate">{c.cancellation_reason}</span>
                                                                        </span>
                                                                    )}
                                                                    {c.status === 'closed' && c.rating && (
                                                                        <span
                                                                            className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 text-xs font-bold"
                                                                            title={c.feedback ? `ข้อเสนอแนะ: ${c.feedback}` : undefined}
                                                                        >
                                                                            <span>⭐</span>
                                                                            <span>{c.rating}/5</span>
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </td>
                                                            <td className="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">
                                                                {formatThaiDate(c.created_at)}
                                                            </td>
                                                            <td className="px-5 py-3.5 whitespace-nowrap text-right text-sm font-medium">
                                                                <div className="flex items-center justify-end gap-2">
                                                                    {c.status === 'resolved' && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => handleOpenCsatModal(c)}
                                                                            className="inline-flex items-center gap-1 text-white bg-amber-500 hover:bg-amber-600 px-3 py-1.5 rounded-lg transition font-bold text-xs shadow-xs hover:shadow cursor-pointer"
                                                                        >
                                                                            <span>⭐ ยืนยันรับงาน</span>
                                                                        </button>
                                                                    )}
                                                                    <a
                                                                        href={route('tickets.show', c.id)}
                                                                        className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition font-medium text-xs cursor-pointer"
                                                                    >
                                                                        <span>ดูรายละเอียด</span>
                                                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                                                        </svg>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Pagination */}
                                    <div className="mt-6 flex flex-wrap justify-between items-center gap-3 pt-4 border-t border-gray-100">
                                        <div className="flex items-center gap-3 text-xs text-gray-500">
                                            <span>
                                                แสดง {cases.from || 0} ถึง {cases.to || 0} จากทั้งหมด {cases.total || 0} รายการ
                                            </span>
                                            <div className="flex items-center gap-1.5 border-l border-gray-200 pl-3">
                                                <span>ต่อหน้า:</span>
                                                <select
                                                    value={currentPerPage}
                                                    onChange={(e) => handlePerPageChange(Number(e.target.value))}
                                                    aria-label="เลือกจำนวนรายการต่อหน้าส่วนล่าง"
                                                    className="text-xs border-gray-200 rounded-lg py-1 pl-2.5 pr-8 bg-white text-gray-700 focus:ring-blue-500 focus:border-blue-500 shadow-2xs cursor-pointer"
                                                >
                                                    <option value={10}>10</option>
                                                    <option value={25}>25</option>
                                                    <option value={50}>50</option>
                                                </select>
                                            </div>
                                        </div>
                                        {cases.links && cases.links.length > 3 && (
                                            <div className="flex flex-wrap gap-1">
                                                {cases.links.map((link, idx) => (
                                                    <button
                                                        key={idx}
                                                        disabled={!link.url || link.active}
                                                        onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                        className={`px-3 py-1.5 text-xs rounded-lg border transition ${
                                                            link.active
                                                                ? 'bg-blue-600 text-white border-blue-600 font-bold shadow-sm'
                                                                : link.url
                                                                ? 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200 cursor-pointer'
                                                                : 'bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed'
                                                        }`}
                                                    />
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </>
                            )}
                        </div>
                    </div>

                </div>
            </div>

            {/* 1-Click Acceptance CSAT Modal */}
            <Modal show={showCsatModal} onClose={() => !isSubmittingCsat && setShowCsatModal(false)} maxWidth="md">
                <div className="p-6 sm:p-8 bg-white rounded-3xl">
                    <div className="text-center mb-5">
                        <div className="w-14 h-14 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-inner">
                            <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <h3 className="text-xl font-black text-slate-900 tracking-tight">
                            ยืนยันรับงาน & ประเมินความพึงพอใจ
                        </h3>
                        {selectedTicketForCsat && (
                            <div className="mt-2 p-2 bg-slate-50 rounded-xl border border-slate-100 inline-block max-w-full">
                                <span className="font-mono font-bold text-xs text-blue-600 mr-1.5">#{selectedTicketForCsat.ticket_no}</span>
                                <span className="text-xs text-slate-700 font-medium truncate">{selectedTicketForCsat.title}</span>
                            </div>
                        )}
                        <p className="text-xs text-slate-500 mt-2">
                            กรุณาให้คะแนนการให้บริการของเจ้าหน้าที่ เพื่อช่วยให้เราพัฒนาบริการให้ดียิ่งขึ้น
                        </p>
                    </div>

                    {/* 5-Star Rating */}
                    <div className="flex flex-col items-center mb-6">
                        <div className="flex justify-center gap-2 mb-2">
                            {[1, 2, 3, 4, 5].map((star) => (
                                <button
                                    key={star}
                                    type="button"
                                    onClick={() => setCsatRating(star)}
                                    onMouseEnter={() => setCsatHover(star)}
                                    onMouseLeave={() => setCsatHover(0)}
                                    className="focus:outline-none transform hover:scale-115 active:scale-95 transition-all p-1 cursor-pointer"
                                >
                                    <svg
                                        className={`w-10 h-10 transition-colors duration-150 drop-shadow-sm ${
                                            (csatHover || csatRating) >= star ? 'text-amber-400 fill-current' : 'text-slate-200 fill-current'
                                        }`}
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </button>
                            ))}
                        </div>
                        <span className="text-xs font-bold text-amber-700 bg-amber-50 px-3 py-1 rounded-full border border-amber-200">
                            {csatRating === 5 && '⭐⭐⭐⭐⭐ ยอดเยี่ยมมาก'}
                            {csatRating === 4 && '⭐⭐⭐⭐ ดีมาก'}
                            {csatRating === 3 && '⭐⭐⭐ พอใช้'}
                            {csatRating === 2 && '⭐⭐ ต้องปรับปรุง'}
                            {csatRating === 1 && '⭐ ไม่พึงพอใจ'}
                        </span>
                    </div>

                    {/* Feedback Input */}
                    <div className="mb-6">
                        <label className="block text-xs font-bold text-slate-700 mb-1.5">
                            ข้อเสนอแนะเพิ่มเติม (ถ้ามี)
                        </label>
                        <textarea
                            rows={3}
                            value={csatFeedback}
                            onChange={(e) => setCsatFeedback(e.target.value)}
                            placeholder="พิมพ์ข้อเสนอแนะ หรือคำชมเชยที่นี่..."
                            className="w-full rounded-2xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500 leading-normal"
                        />
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            disabled={isSubmittingCsat}
                            onClick={() => setShowCsatModal(false)}
                            className="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-sm transition cursor-pointer"
                        >
                            ยกเลิก
                        </button>
                        <button
                            type="button"
                            disabled={isSubmittingCsat}
                            onClick={handleSubmitCsat}
                            className="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold rounded-xl text-sm shadow-md hover:shadow-lg transition cursor-pointer disabled:opacity-50 flex items-center gap-2"
                        >
                            {isSubmittingCsat ? (
                                <>
                                    <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                    <span>กำลังบันทึก...</span>
                                </>
                            ) : (
                                <span>ยืนยันรับงานและส่งคะแนน</span>
                            )}
                        </button>
                    </div>
                </div>
            </Modal>

            {/* Mobile Floating Action Button (FAB) - เปิดเคสใหม่ */}
            <div className="md:hidden fixed bottom-6 right-6 z-40">
                <a
                    href={route('tickets.create')}
                    className="flex items-center gap-2 px-5 py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-full shadow-xl hover:shadow-2xl active:scale-95 transition-all duration-200 border border-blue-400/30 font-bold text-sm tracking-wide cursor-pointer"
                    title="เปิดใบงานใหม่"
                >
                    <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>แจ้งซ่อม</span>
                </a>
            </div>
        </>
    );
}
