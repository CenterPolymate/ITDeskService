import React, { useState, useEffect, useRef } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { sendBrowserNotification } from '@/Utils/webNotification';

export default function UserDashboard({ cases, stats, filters = {} }) {
    const { auth, flash } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const currentStatus = filters.status || 'all';
    const knownCasesRef = useRef(null);

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
                    body = `${t.title || 'กำลังดำเนินการ'}\nช่างเฉพาะทางกำลังดำเนินการแก้ไขปัญหาของคุณ`;
                } else if (t.status === 'resolved') {
                    title = `🎉 ช่างส่งมอบงานแล้ว: #${t.ticket_no}`;
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
                    onClick: () => {
                        window.location.href = route('tickets.show', t.id);
                    },
                });
            }
            knownCasesRef.current.set(t.id, t.status);
        });
    }, [cases]);

    // Auto-polling updates every 15 seconds
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({
                only: ['cases', 'stats'],
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

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        router.get(
            route('dashboard'),
            {
                status: currentStatus !== 'all' ? currentStatus : undefined,
                search: search || undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleClearSearch = () => {
        setSearch('');
        router.get(
            route('dashboard'),
            {
                status: currentStatus !== 'all' ? currentStatus : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleFilterStatus = (statusKey) => {
        router.get(
            route('dashboard'),
            {
                status: statusKey !== 'all' ? statusKey : undefined,
                search: search || undefined,
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
                        <div className="bg-gradient-to-r from-emerald-600 to-teal-500 rounded-2xl p-5 shadow-lg flex flex-col sm:flex-row items-center justify-between text-white overflow-hidden relative border border-emerald-400">
                            <div className="absolute right-0 top-0 w-48 h-48 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-2xl translate-x-12 -translate-y-12 pointer-events-none" />
                            <div className="absolute left-0 bottom-0 w-32 h-32 bg-yellow-300 opacity-20 rounded-full mix-blend-overlay filter blur-xl -translate-x-10 translate-y-10 pointer-events-none" />

                            <div className="flex items-center gap-4 z-10 w-full sm:w-auto mb-4 sm:mb-0">
                                <div className="bg-white/20 p-3 rounded-xl animate-bounce shrink-0">
                                    <svg className="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-lg sm:text-xl font-bold tracking-wide">แจ้งเตือน: มีใบงานรอให้คุณตรวจสอบ</h3>
                                    <p className="text-emerald-100 text-xs sm:text-sm mt-0.5">
                                        ทีมงานได้ทำการแก้ไขใบงานของคุณเสร็จสิ้นแล้ว จำนวน <span className="font-black text-base px-1 bg-white/20 rounded mx-0.5">{stats.waiting_acceptance}</span> รายการ กรุณาเข้าไปตรวจสอบและกดยืนยันรับงาน
                                    </p>
                                </div>
                            </div>

                            <div className="z-10 w-full sm:w-auto text-center shrink-0">
                                <button
                                    type="button"
                                    onClick={() => handleFilterStatus('resolved')}
                                    className="inline-block w-full sm:w-auto bg-white text-emerald-700 font-bold py-2.5 px-6 rounded-xl shadow-md hover:bg-emerald-50 hover:shadow-lg transform transition hover:-translate-y-0.5 text-sm"
                                >
                                    ดูใบงานที่ต้องยืนยัน
                                </button>
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
                                    ยินดีต้อนรับสู่ระบบแจ้งซ่อม ITDeskService คุณสามารถติดตามสถานะการแจ้งซ่อม หรือเปิดใบงานใหม่ได้ที่นี่
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
                                <div className="flex items-center gap-3">
                                    <h3 className="text-lg font-bold text-gray-900">ประวัติการแจ้งซ่อมของคุณ</h3>
                                    {currentStatus !== 'all' && (
                                        <button
                                            type="button"
                                            onClick={() => handleFilterStatus('all')}
                                            className="text-xs text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-full transition flex items-center gap-1 font-medium"
                                        >
                                            <span>ล้างตัวกรองสถานะ</span>
                                            <span>✕</span>
                                        </button>
                                    )}
                                </div>

                                <form onSubmit={handleSearchSubmit} className="w-full sm:w-80">
                                    <div className="relative">
                                        <input
                                            type="text"
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            placeholder="ค้นหาใบงาน, หัวข้อปัญหา..."
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
                                    {/* Mobile Cards View (md:hidden) */}
                                    <div className="md:hidden space-y-3">
                                        {cases.data.map((c) => (
                                            <a
                                                key={c.id}
                                                href={route('tickets.show', c.id)}
                                                className="block bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition p-4 active:bg-gray-50"
                                            >
                                                <div className="flex justify-between items-start mb-2">
                                                    <span className="text-sm font-bold text-blue-600">
                                                        #{c.ticket_no}
                                                    </span>
                                                    <div className="flex flex-col items-end gap-1">
                                                        {getStatusBadge(c.status, c.status_label)}
                                                        {c.task2_status_label && (
                                                            <span className={`px-2 py-0.5 text-[10px] font-bold rounded-full ${c.task2_status_color || 'bg-gray-100 text-gray-700'}`}>
                                                                {c.task2_status_label}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>

                                                <h4 className="text-base font-semibold text-gray-900 mb-2 leading-tight">
                                                    {c.title}
                                                </h4>

                                                <div className="flex justify-between items-center text-xs text-gray-500 pt-3 border-t border-gray-100">
                                                    <span className="flex items-center gap-1">
                                                        <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                        </svg>
                                                        {c.category || 'อื่นๆ'}
                                                    </span>
                                                    <span className="flex items-center gap-1">
                                                        <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        {formatThaiDate(c.created_at)}
                                                    </span>
                                                </div>
                                            </a>
                                        ))}
                                    </div>

                                    {/* Desktop Table View (hidden md:block) */}
                                    <div className="hidden md:block overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200">
                                            <thead className="bg-gray-50/80 rounded-lg">
                                                <tr>
                                                    <th scope="col" className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                        Ticket No
                                                    </th>
                                                    <th scope="col" className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                        หัวข้อปัญหา
                                                    </th>
                                                    <th scope="col" className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                        หมวดหมู่
                                                    </th>
                                                    <th scope="col" className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                        สถานะ
                                                    </th>
                                                    <th scope="col" className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                        วันที่แจ้ง
                                                    </th>
                                                    <th scope="col" className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                        จัดการ
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white divide-y divide-gray-100">
                                                {cases.data.map((c) => (
                                                    <tr key={c.id} className="hover:bg-blue-50/40 transition-colors">
                                                        <td className="px-5 py-3.5 whitespace-nowrap text-sm font-bold">
                                                            <a
                                                                href={route('tickets.show', c.id)}
                                                                className="text-blue-600 hover:text-blue-800 hover:underline"
                                                            >
                                                                #{c.ticket_no}
                                                            </a>
                                                        </td>
                                                        <td className="px-5 py-3.5 text-sm text-gray-900 font-medium max-w-md truncate">
                                                            {c.title}
                                                        </td>
                                                        <td className="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">
                                                            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 text-xs">
                                                                {c.category || 'อื่นๆ'}
                                                            </span>
                                                        </td>
                                                        <td className="px-5 py-3.5 whitespace-nowrap text-sm">
                                                            <div className="flex flex-col items-start gap-1">
                                                                {getStatusBadge(c.status, c.status_label)}
                                                                {c.task2_status_label && (
                                                                    <span className={`px-2 py-0.5 text-[10px] font-bold rounded-full ${c.task2_status_color || 'bg-gray-100 text-gray-700'}`}>
                                                                        {c.task2_status_label}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td className="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">
                                                            {formatThaiDate(c.created_at)}
                                                        </td>
                                                        <td className="px-5 py-3.5 whitespace-nowrap text-right text-sm font-medium">
                                                            <a
                                                                href={route('tickets.show', c.id)}
                                                                className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition font-medium text-xs"
                                                            >
                                                                <span>ดูรายละเอียด</span>
                                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                                                </svg>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Pagination */}
                                    {cases.links && cases.links.length > 3 && (
                                        <div className="mt-6 flex flex-wrap justify-between items-center gap-3 pt-4 border-t border-gray-100">
                                            <div className="text-xs text-gray-500">
                                                แสดง {cases.from || 0} ถึง {cases.to || 0} จากทั้งหมด {cases.total || 0} รายการ
                                            </div>
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
                                                                ? 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200'
                                                                : 'bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed'
                                                        }`}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    </div>

                </div>
            </div>
        </>
    );
}
