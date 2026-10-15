import React, { useState, useEffect, useRef } from 'react';
import { Head, router, Link, usePage } from '@inertiajs/react';
import {
    isNotificationSupported,
    getNotificationPermission,
    requestNotificationPermission,
    sendBrowserNotification,
    playNotificationChime,
} from '@/Utils/webNotification';

export default function ItStaffDashboard({ cases, stats, filters = {} }) {
    const { auth, flash } = usePage().props;
    const userRole = auth.user.role;

    const [formFilters, setFormFilters] = useState({
        status: filters.status || 'all',
        priority: filters.priority || 'all',
        team: filters.team || 'all',
        search: filters.search || '',
    });

    const [isTyping, setIsTyping] = useState(false);
    const [notificationPermission, setNotificationPermission] = useState('default');
    const knownUrgentIdsRef = useRef(null);

    useEffect(() => {
        setNotificationPermission(getNotificationPermission());
    }, []);

    const handleRequestPermission = async () => {
        const res = await requestNotificationPermission();
        setNotificationPermission(res);
        if (res === 'granted') {
            sendBrowserNotification('✅ เปิดการแจ้งเตือนสำเร็จ', {
                body: 'ระบบจะส่งเสียงและป๊อปอัปแจ้งเตือนทันทีเมื่อมีเคสด่วนที่สุดเข้ามา',
            });
        }
    };

    const knownTicketsMapRef = useRef(null);

    // Watch for new tickets and status changes during polling
    useEffect(() => {
        const ticketList = cases?.data || (Array.isArray(cases) ? cases : []);

        // First load: memorize existing tickets and statuses
        if (knownTicketsMapRef.current === null) {
            knownTicketsMapRef.current = new Map(ticketList.map(c => [c.id, c.status]));
            return;
        }

        // On subsequent polls, check for new tickets or status updates
        ticketList.forEach(t => {
            const isKnown = knownTicketsMapRef.current.has(t.id);
            const prevStatus = knownTicketsMapRef.current.get(t.id);

            if (!isKnown) {
                // Brand new ticket arrived for this user's scope
                let title = `🔔 มีเคสใหม่เข้ามา: #${t.ticket_no}`;
                let body = `${t.title || 'เคสใหม่'}\nผู้แจ้ง: ${t.requester_name || '-'} (${t.department || '-'})`;

                if (t.priority === 'urgent') {
                    title = `🚨 เคสด่วนที่สุดใหม่: #${t.ticket_no}`;
                } else if (userRole.startsWith('team_')) {
                    title = `🛠️ มีเคสใหม่ส่งถึงทีมคุณ: #${t.ticket_no}`;
                } else if (userRole === 'helpdesk') {
                    title = `📥 มีเคสใหม่รอคัดกรอง (Triage): #${t.ticket_no}`;
                } else if (userRole === 'manager') {
                    title = `📋 มีเคสใหม่ในระบบ: #${t.ticket_no}`;
                }

                sendBrowserNotification(title, {
                    body,
                    tag: `staff-ticket-new-${t.id}`,
                    onClick: () => {
                        window.location.href = route('tickets.show', t.id);
                    },
                });
            } else if (prevStatus && prevStatus !== t.status) {
                // Ticket status changed
                if (t.status === 'resolved') {
                    sendBrowserNotification(`🎉 เคส #${t.ticket_no} ส่งมอบงานแล้ว`, {
                        body: `รอผู้แจ้งตรวจสอบและกดยืนยันรับงาน`,
                        tag: `staff-ticket-status-${t.id}-${t.status}`,
                        onClick: () => {
                            window.location.href = route('tickets.show', t.id);
                        },
                    });
                }
            }

            knownTicketsMapRef.current.set(t.id, t.status);
        });
    }, [cases, userRole]);

    // Live polling updates every 15 seconds (paused if user is typing or focusing on inputs)
    useEffect(() => {
        const interval = setInterval(() => {
            if (!isTyping) {
                router.reload({
                    only: ['cases', 'stats'],
                    preserveScroll: true,
                    preserveState: true,
                });
            }
        }, 15000);

        return () => clearInterval(interval);
    }, [isTyping]);

    const formatRelativeTime = (dateStr) => {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        const now = new Date();
        const diffInSeconds = Math.floor((now - d) / 1000);

        if (diffInSeconds < 60) return 'เมื่อสักครู่';
        const diffInMinutes = Math.floor(diffInSeconds / 60);
        if (diffInMinutes < 60) return `${diffInMinutes} นาทีที่แล้ว`;
        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) return `${diffInHours} ชั่วโมงที่แล้ว`;
        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 30) return `${diffInDays} วันที่แล้ว`;

        return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' });
    };

    const handleFilterChange = (key, value) => {
        setFormFilters(prev => ({ ...prev, [key]: value }));
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        router.get(
            route('dashboard'),
            {
                status: formFilters.status !== 'all' ? formFilters.status : undefined,
                priority: formFilters.priority !== 'all' ? formFilters.priority : undefined,
                team: formFilters.team !== 'all' ? formFilters.team : undefined,
                search: formFilters.search || undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleClearFilters = () => {
        const cleared = { status: 'all', priority: 'all', team: 'all', search: '' };
        setFormFilters(cleared);
        router.get(route('dashboard'), {}, { preserveState: true, preserveScroll: true });
    };

    const handleQuickFilter = (statusKey) => {
        setFormFilters(prev => ({ ...prev, status: statusKey }));
        router.get(
            route('dashboard'),
            {
                status: statusKey !== 'all' ? statusKey : undefined,
                priority: formFilters.priority !== 'all' ? formFilters.priority : undefined,
                team: formFilters.team !== 'all' ? formFilters.team : undefined,
                search: formFilters.search || undefined,
            },
            { preserveState: true, preserveScroll: true }
        );

        // Smooth scroll to table
        const el = document.getElementById('tickets-table');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
        }
    };

    const getPriorityBadge = (priority, label) => {
        const pColors = {
            urgent: 'bg-red-100 text-red-800 border-red-200 font-bold',
            high: 'bg-orange-100 text-orange-800 border-orange-200 font-bold',
            medium: 'bg-yellow-100 text-yellow-800 border-yellow-200 font-medium',
            low: 'bg-blue-100 text-blue-800 border-blue-200',
        };
        const pClass = pColors[priority] || 'bg-gray-100 text-gray-800 border-gray-200';

        return (
            <span className={`px-2.5 py-1 inline-flex text-xs font-semibold rounded-md border ${pClass}`}>
                {label || priority}
            </span>
        );
    };

    const getStatusBadge = (status, label) => {
        const statusColors = {
            pending: 'bg-yellow-100 text-yellow-700',
            analyzing: 'bg-purple-100 text-purple-700',
            in_progress: 'bg-blue-100 text-blue-700',
            resolved: 'bg-cyan-100 text-cyan-700',
            approved: 'bg-amber-100 text-amber-700',
            closed: 'bg-green-100 text-green-800',
            cancelled: 'bg-red-100 text-red-700',
        };
        const sColor = statusColors[status] || 'bg-gray-100 text-gray-800';

        return (
            <span className={`px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full shadow-sm ${sColor}`}>
                {label || status}
            </span>
        );
    };

    return (
        <>
            <Head title="แดชบอร์ดฝ่าย IT - ITDeskService" />

            <div className="min-h-screen pb-12 pt-6 bg-slate-50">
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

                    {/* SLA Warning / Overdue Banner */}
                    {(Number(stats.sla_warning) > 0 || Number(stats.sla_overdue) > 0) && (
                        <div className="bg-gradient-to-r from-amber-600 via-orange-600 to-rose-600 rounded-2xl p-5 shadow-lg flex flex-col sm:flex-row items-center justify-between text-white overflow-hidden relative border border-orange-400">
                            <div className="absolute right-0 top-0 w-48 h-48 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-2xl translate-x-12 -translate-y-12 pointer-events-none" />
                            <div className="absolute left-0 bottom-0 w-32 h-32 bg-yellow-300 opacity-20 rounded-full mix-blend-overlay filter blur-xl -translate-x-10 translate-y-10 pointer-events-none" />
                            <div className="flex items-center gap-5 z-10 w-full sm:w-auto mb-4 sm:mb-0">
                                <div className="bg-white/20 p-3 rounded-xl animate-pulse shrink-0">
                                    <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-xl font-bold tracking-wide">แจ้งเตือน SLA: พบใบงานที่ต้องเร่งดำเนินการ</h3>
                                    <p className="text-orange-100 text-sm mt-1">
                                        {Number(stats.sla_warning) > 0 && (
                                            <span>
                                                ใกล้หมดเวลา SLA (&lt; 1 ชม.): <span className="font-black text-lg px-1.5 py-0.5 bg-white/20 rounded mx-1">{Number(stats.sla_warning).toLocaleString()}</span> รายการ
                                            </span>
                                        )}
                                        {Number(stats.sla_warning) > 0 && Number(stats.sla_overdue) > 0 && <span> | </span>}
                                        {Number(stats.sla_overdue) > 0 && (
                                            <span className="text-rose-100 font-bold">
                                                เกินกำหนดเวลา SLA แล้ว: <span className="font-black text-lg px-1.5 py-0.5 bg-rose-900/50 rounded mx-1 text-white">{Number(stats.sla_overdue).toLocaleString()}</span> รายการ
                                            </span>
                                        )}
                                    </p>
                                </div>
                            </div>
                            <div className="z-10 w-full sm:w-auto flex flex-wrap gap-2 text-center shrink-0">
                                {Number(stats.sla_warning) > 0 && (
                                    <button
                                        onClick={() => handleQuickFilter('sla_warning')}
                                        className="flex-1 sm:flex-initial bg-white text-amber-800 font-bold py-2 px-4 rounded-xl shadow-md hover:bg-amber-50 hover:shadow-lg transform transition hover:-translate-y-0.5 cursor-pointer text-sm"
                                    >
                                        ดูเคสใกล้หมดเวลา
                                    </button>
                                )}
                                {Number(stats.sla_overdue) > 0 && (
                                    <button
                                        onClick={() => handleQuickFilter('sla_overdue')}
                                        className="flex-1 sm:flex-initial bg-rose-900 text-white font-bold py-2 px-4 rounded-xl shadow-md hover:bg-rose-950 hover:shadow-lg transform transition hover:-translate-y-0.5 cursor-pointer text-sm"
                                    >
                                        ดูเคสเกินกำหนด SLA
                                    </button>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Urgent Banner */}
                    {stats.urgent > 0 && (
                        <div className="bg-gradient-to-r from-red-600 to-red-500 rounded-2xl p-5 shadow-lg flex flex-col sm:flex-row items-center justify-between text-white overflow-hidden relative border border-red-400">
                            <div className="absolute right-0 top-0 w-48 h-48 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-2xl translate-x-12 -translate-y-12 pointer-events-none" />
                            <div className="absolute left-0 bottom-0 w-32 h-32 bg-yellow-300 opacity-20 rounded-full mix-blend-overlay filter blur-xl -translate-x-10 translate-y-10 pointer-events-none" />
                            <div className="flex items-center gap-5 z-10 w-full sm:w-auto mb-4 sm:mb-0">
                                <div className="bg-white/20 p-3 rounded-xl animate-bounce shrink-0">
                                    <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-xl font-bold tracking-wide">แจ้งเตือนด่วน! มีเคสที่ต้องจัดการทันที</h3>
                                    <p className="text-red-100 text-sm mt-1">
                                        พบใบงานระดับความเร่งด่วน "ด่วนที่สุด" จำนวน <span className="font-black text-lg px-1.5 py-0.5 bg-white/20 rounded mx-1">{Number(stats.urgent).toLocaleString()}</span> รายการที่รอการแก้ไข
                                    </p>
                                </div>
                            </div>
                            <div className="z-10 w-full sm:w-auto text-center shrink-0">
                                <button
                                    onClick={() => {
                                        setFormFilters(prev => ({ ...prev, priority: 'urgent', status: 'not_closed' }));
                                        router.get(route('dashboard'), { priority: 'urgent', status: 'not_closed' }, { preserveState: true, preserveScroll: true });
                                    }}
                                    className="w-full sm:w-auto bg-white text-red-600 font-bold py-2.5 px-6 rounded-xl shadow-md hover:bg-gray-50 hover:shadow-lg transform transition hover:-translate-y-0.5 cursor-pointer"
                                >
                                    ดูเคสด่วนทั้งหมด
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Manager Approval Banner */}
                    {userRole === 'manager' && stats.task2_manager_review > 0 && (
                        <div className="bg-gradient-to-r from-amber-600 to-amber-500 rounded-2xl p-5 shadow-lg flex flex-col sm:flex-row items-center justify-between text-white overflow-hidden relative border border-amber-400">
                            <div className="absolute right-0 top-0 w-48 h-48 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-2xl translate-x-12 -translate-y-12 pointer-events-none" />
                            <div className="absolute left-0 bottom-0 w-32 h-32 bg-yellow-300 opacity-20 rounded-full mix-blend-overlay filter blur-xl -translate-x-10 translate-y-10 pointer-events-none" />
                            <div className="flex items-center gap-5 z-10 w-full sm:w-auto mb-4 sm:mb-0">
                                <div className="bg-white/20 p-3 rounded-xl animate-pulse shrink-0">
                                    <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-xl font-bold tracking-wide">แจ้งเตือน: งานรอหัวหน้าตรวจสอบ</h3>
                                    <p className="text-amber-100 text-sm mt-1">
                                        พบใบงานสถานะ "ปิดใบงานแล้ว" จำนวน <span className="font-black text-lg px-1.5 py-0.5 bg-white/20 rounded mx-1">{Number(stats.task2_manager_review).toLocaleString()}</span> รายการที่รอการตรวจสอบมาตรการป้องกัน
                                    </p>
                                </div>
                            </div>
                            <div className="z-10 w-full sm:w-auto text-center shrink-0">
                                <button
                                    onClick={() => handleQuickFilter('task2_manager_review')}
                                    className="w-full sm:w-auto bg-white text-amber-600 font-bold py-2.5 px-6 rounded-xl shadow-md hover:bg-gray-50 hover:shadow-lg transform transition hover:-translate-y-0.5 cursor-pointer"
                                >
                                    ดูเคสรอตรวจสอบทั้งหมด
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Task 1 Section Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                        <div className="flex items-center">
                            <div className="bg-blue-600 w-1.5 h-6 rounded-full mr-3" />
                            <h2 className="text-xl font-bold text-gray-800">Task 1: สถานะการแจ้งซ่อม (Ticketing)</h2>
                        </div>
                        <div className="flex items-center gap-2">
                            {/* Browser Notification Toggle / Status */}
                            {isNotificationSupported() && (
                                notificationPermission === 'granted' ? (
                                    <button
                                        type="button"
                                        onClick={() => playNotificationChime(true)}
                                        title="คลิกเพื่อทดสอบเสียงแจ้งเตือน"
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 rounded-full text-xs font-semibold transition cursor-pointer shadow-sm"
                                    >
                                        <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                                        <span>🔔 แจ้งเตือนเปิดอยู่ (ทดสอบเสียง)</span>
                                    </button>
                                ) : notificationPermission === 'denied' ? (
                                    <span
                                        title="เบราว์เซอร์บล็อกการแจ้งเตือน สามารถเปิดได้ที่การตั้งค่าไซต์ของเบราว์เซอร์"
                                        className="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-medium border border-gray-200"
                                    >
                                        <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        <span>ปิดแจ้งเตือนในบราวเซอร์</span>
                                    </span>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={handleRequestPermission}
                                        title="เปิดรับการแจ้งเตือนเสียงและป๊อปอัปเมื่อมีเคสด่วนเข้ามา"
                                        className="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full text-xs font-semibold shadow-sm transition cursor-pointer"
                                    >
                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                        <span>เปิดแจ้งเตือนเคสด่วน (Alerts)</span>
                                    </button>
                                )
                            )}
                            <span className="hidden lg:inline-flex text-xs font-semibold px-2.5 py-1 bg-white text-gray-500 rounded-full border border-gray-200">
                                คลิกที่การ์ดเพื่อกรองตามสถานะ
                            </span>
                        </div>
                    </div>

                    {/* Task 1 KPI Cards Row */}
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-4 auto-rows-fr">
                        {/* 1. Total Cases */}
                        <div
                            onClick={() => handleQuickFilter('all')}
                            className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-blue-200 hover:border-blue-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                        >
                            <div className="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8" />
                            <p className="text-sm font-bold text-blue-800 mb-2 leading-tight group-hover:text-blue-900">เคสทั้งหมด</p>
                            <div className="flex items-end justify-between mt-auto">
                                <span className="text-4xl font-black text-gray-900 leading-none">{Number(stats.total).toLocaleString()}</span>
                                <svg className="w-7 h-7 text-blue-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>

                        {/* 2. Pending Cases */}
                        <div
                            onClick={() => handleQuickFilter('pending')}
                            className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-yellow-200 hover:border-yellow-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                        >
                            <div className="absolute right-0 top-0 w-24 h-24 bg-yellow-50 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8" />
                            <p className="text-sm font-bold text-yellow-800 mb-2 leading-tight group-hover:text-yellow-900">รอดำเนินการ / มอบหมาย</p>
                            <div className="flex items-end justify-between mt-auto">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-black text-yellow-600 leading-none">{Number(stats.pending).toLocaleString()}</span>
                                    <span className="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{stats.percent_pending}%</span>
                                </div>
                                <svg className="w-7 h-7 text-yellow-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                                </svg>
                            </div>
                        </div>

                        {/* 3. Analyzing Cases */}
                        <div
                            onClick={() => handleQuickFilter('analyzing')}
                            className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-purple-200 hover:border-purple-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                        >
                            <div className="absolute right-0 top-0 w-24 h-24 bg-purple-50 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8" />
                            <p className="text-sm font-bold text-purple-800 mb-2 leading-tight group-hover:text-purple-900">สืบสภาพ / วิเคราะห์สาเหตุ</p>
                            <div className="flex items-end justify-between mt-auto">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-black text-purple-600 leading-none">{Number(stats.analyzing).toLocaleString()}</span>
                                    <span className="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{stats.percent_analyzing}%</span>
                                </div>
                                <svg className="w-7 h-7 text-purple-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        {/* 4. In Progress Cases */}
                        <div
                            onClick={() => handleQuickFilter('in_progress')}
                            className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-indigo-200 hover:border-indigo-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                        >
                            <div className="absolute right-0 top-0 w-24 h-24 bg-indigo-50 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8" />
                            <p className="text-sm font-bold text-indigo-800 mb-2 leading-tight group-hover:text-indigo-900">ดำเนินการแก้ไข</p>
                            <div className="flex items-end justify-between mt-auto">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-black text-indigo-600 leading-none">{Number(stats.in_progress).toLocaleString()}</span>
                                    <span className="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{stats.percent_in_progress}%</span>
                                </div>
                                <svg className="w-7 h-7 text-indigo-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                        </div>

                        {/* 5. Testing & Delivery */}
                        <div
                            onClick={() => handleQuickFilter('testing')}
                            className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-cyan-200 hover:border-cyan-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                        >
                            <div className="absolute right-0 top-0 w-24 h-24 bg-cyan-50 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8" />
                            <p className="text-sm font-bold text-cyan-800 mb-2 leading-tight group-hover:text-cyan-900">ทดสอบและส่งมอบ</p>
                            <div className="flex items-end justify-between mt-auto">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-black text-cyan-600 leading-none">{Number(stats.testing).toLocaleString()}</span>
                                    <span className="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{stats.percent_testing}%</span>
                                </div>
                                <svg className="w-7 h-7 text-cyan-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                        </div>

                        {/* 6. Completed Cases (Closed + Cancelled) */}
                        <div
                            onClick={() => handleQuickFilter('completed')}
                            className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-emerald-200 hover:border-emerald-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                        >
                            <div className="absolute right-0 top-0 w-24 h-24 bg-emerald-50 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8" />
                            <p className="text-sm font-bold text-emerald-800 mb-2 leading-tight group-hover:text-emerald-900">แก้ไขเสร็จ / ปิดใบงาน</p>
                            <div className="flex items-end justify-between mt-auto">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-black text-emerald-600 leading-none">{Number(stats.completed).toLocaleString()}</span>
                                    <span className="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{stats.percent_completed}%</span>
                                </div>
                                <div className="flex flex-col gap-1 items-end">
                                    <span className="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded flex items-center gap-1">
                                        ปิดงาน {Number(stats.closed).toLocaleString()}
                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                                    </span>
                                    <span className="text-[10px] font-bold text-red-700 bg-red-50 border border-red-200 px-1.5 py-0.5 rounded flex items-center gap-1">
                                        ยกเลิก {Number(stats.cancelled).toLocaleString()}
                                        <span className="w-1.5 h-1.5 rounded-full bg-red-500" />
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* 7. Breached SLA Cases */}
                        <div
                            onClick={() => handleQuickFilter('breached')}
                            className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-red-200 hover:border-red-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                        >
                            <div className="absolute right-0 top-0 w-24 h-24 bg-red-50 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8" />
                            <p className="text-sm font-bold text-red-800 mb-2 leading-tight group-hover:text-red-900">ซ่อมเสร็จเกิน SLA</p>
                            <div className="flex items-end justify-between mt-auto">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-black text-red-600 leading-none">{Number(stats.breached || 0).toLocaleString()}</span>
                                    <span className="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{stats.percent_breached || 0}%</span>
                                </div>
                                <svg className="w-7 h-7 text-red-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Task 2 Section (Preventive Action) - Hidden for Helpdesk */}
                    {userRole !== 'helpdesk' && (
                        <>
                            <div className="flex items-center pt-2">
                                <div className="bg-teal-600 w-1.5 h-6 rounded-full mr-3" />
                                <h2 className="text-xl font-bold text-gray-800">
                                    Task 2: การป้องกันปัญหาไม่ให้เกิดซ้ำ (Preventive Action)
                                </h2>
                            </div>

                            <div className={`grid grid-cols-2 md:grid-cols-3 ${['manager', 'administrator'].includes(userRole) ? 'lg:grid-cols-6' : 'lg:grid-cols-5'} gap-4 auto-rows-fr`}>
                                {/* 1. Task 2 All Cases */}
                                <div
                                    onClick={() => handleQuickFilter('task2_all')}
                                    className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-indigo-200 hover:border-indigo-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                                >
                                    <p className="text-sm font-bold text-indigo-800 mb-2 leading-tight group-hover:text-indigo-900">เคสทั้งหมด</p>
                                    <div className="flex items-end justify-between mt-auto">
                                        <span className="text-4xl font-black text-indigo-600 leading-none">{Number(stats.task2_total).toLocaleString()}</span>
                                        <svg className="w-7 h-7 text-indigo-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                </div>

                                {/* 2. Manager Review Cases (Only for manager and admin) */}
                                {['manager', 'administrator'].includes(userRole) && (
                                    <div
                                        onClick={() => handleQuickFilter('task2_manager_review')}
                                        className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-orange-200 hover:border-orange-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                                    >
                                        <p className="text-sm font-bold text-orange-800 mb-2 leading-tight group-hover:text-orange-900">รอหัวหน้าตรวจสอบ</p>
                                        <div className="flex items-end justify-between mt-auto">
                                            <div className="flex items-baseline gap-2">
                                                <span className="text-4xl font-black text-orange-600 leading-none">{Number(stats.task2_manager_review).toLocaleString()}</span>
                                                <span className="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{stats.percent_t2_manager}%</span>
                                            </div>
                                            <svg className="w-7 h-7 text-orange-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </div>
                                    </div>
                                )}

                                {/* 3. Assigned / Pending */}
                                <div
                                    onClick={() => handleQuickFilter('task2_assigned')}
                                    className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-rose-200 hover:border-rose-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                                >
                                    <p className="text-sm font-bold text-rose-800 mb-2 leading-tight group-hover:text-rose-900">รอดำเนินการ / มอบหมาย</p>
                                    <div className="flex items-end justify-between mt-auto">
                                        <div className="flex items-baseline gap-2">
                                            <span className="text-4xl font-black text-rose-600 leading-none">{Number(stats.task2_assigned).toLocaleString()}</span>
                                            <span className="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{stats.percent_t2_assigned}%</span>
                                        </div>
                                        <svg className="w-7 h-7 text-rose-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>

                                {/* 4. In Progress */}
                                <div
                                    onClick={() => handleQuickFilter('task2_in_progress')}
                                    className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-yellow-300 hover:border-yellow-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                                >
                                    <p className="text-sm font-bold text-yellow-900 mb-2 leading-tight group-hover:text-yellow-950">กำลังดำเนินการป้องกัน</p>
                                    <div className="flex items-end justify-between mt-auto">
                                        <div className="flex items-baseline gap-2">
                                            <span className="text-4xl font-black text-yellow-700 leading-none">{Number(stats.task2_in_progress).toLocaleString()}</span>
                                            <span className="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{stats.percent_t2_in_progress}%</span>
                                        </div>
                                        <svg className="w-7 h-7 text-yellow-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                </div>

                                {/* 5. Pending Review */}
                                <div
                                    onClick={() => handleQuickFilter('task2_pending_review')}
                                    className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-blue-200 hover:border-blue-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                                >
                                    <p className="text-sm font-bold text-blue-800 mb-2 leading-tight group-hover:text-blue-900">ตรวจปิดมาตรการป้องกัน</p>
                                    <div className="flex items-end justify-between mt-auto">
                                        <div className="flex items-baseline gap-2">
                                            <span className="text-4xl font-black text-blue-600 leading-none">{Number(stats.task2_pending_review).toLocaleString()}</span>
                                            <span className="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{stats.percent_t2_pending}%</span>
                                        </div>
                                        <svg className="w-7 h-7 text-blue-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>

                                {/* 6. Completed */}
                                <div
                                    onClick={() => handleQuickFilter('task2_completed')}
                                    className="bg-white rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-teal-200 hover:border-teal-400 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                                >
                                    <p className="text-sm font-bold text-teal-800 mb-2 leading-tight group-hover:text-teal-900">ปิดมาตรการป้องกัน</p>
                                    <div className="flex items-end justify-between mt-auto">
                                        <div className="flex items-baseline gap-2">
                                            <span className="text-4xl font-black text-teal-600 leading-none">{Number(stats.task2_completed).toLocaleString()}</span>
                                            <span className="text-xs font-bold text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{stats.percent_t2_completed}%</span>
                                        </div>
                                        <svg className="w-7 h-7 text-teal-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </>
                    )}

                    {/* Table Section */}
                    <div id="tickets-table" className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-6 scroll-mt-6">
                        <div className="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div>
                                    <h3 className="text-lg font-bold text-gray-900">รายการแจ้งซ่อมที่ต้องจัดการ</h3>
                                    <p className="text-xs text-gray-500 mt-0.5">ค้นหาและกรองใบงานตามความเร่งด่วน ทีม และสถานะ</p>
                                </div>
                                <span className="text-xs font-semibold px-2.5 py-1 bg-white text-gray-600 rounded-lg border border-gray-200">
                                    พบทั้งหมด {cases.total || 0} รายการ
                                </span>
                            </div>

                            {/* Comprehensive Filters */}
                            <form onSubmit={handleSearchSubmit} className="w-full mt-4 p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">สถานะ (Status)</label>
                                        <select
                                            value={formFilters.status}
                                            onChange={(e) => handleFilterChange('status', e.target.value)}
                                            className="w-full rounded-lg border-gray-300 focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white"
                                        >
                                            <option value="all">ทุกสถานะ</option>
                                            <option value="sla_warning">⏰ ใกล้หมดเวลา SLA (&lt; 1 ชม.)</option>
                                            <option value="sla_overdue">⚠️ เกินกำหนดเวลา SLA แล้ว</option>
                                            <option value="not_closed">ยังไม่ปิดใบงาน</option>
                                            <option value="pending">รอดำเนินการ</option>
                                            <option value="active">กำลังทำ (Assigned/Analyzing/In Progress)</option>
                                            <option value="resolved">รอผู้แจ้งกดรับงาน</option>
                                            <option value="approved">รอหัวหน้าปิดใบงาน</option>
                                            <option value="completed">แก้ไขเสร็จ / ปิดใบงาน</option>
                                            <option value="breached">แก้ไขเสร็จเกิน SLA</option>
                                            <option value="task2_all">เคส Task 2 ทั้งหมด</option>
                                            <option value="task2_manager_review">รอหัวหน้าตรวจมาตรการป้องกัน</option>
                                            <option value="task2_assigned">รอดำเนินการป้องกัน</option>
                                            <option value="task2_in_progress">กำลังดำเนินการป้องกัน</option>
                                            <option value="task2_pending_review">กำลังตรวจสอบปิดมาตรการ</option>
                                            <option value="task2_completed">ปิดมาตรการป้องกันแล้ว</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">ความเร่งด่วน (Priority)</label>
                                        <select
                                            value={formFilters.priority}
                                            onChange={(e) => handleFilterChange('priority', e.target.value)}
                                            className="w-full rounded-lg border-gray-300 focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white"
                                        >
                                            <option value="all">ทุกระดับ</option>
                                            <option value="urgent">ด่วนที่สุด (Urgent)</option>
                                            <option value="high">สูง (High)</option>
                                            <option value="medium">ปานกลาง (Medium)</option>
                                            <option value="low">ทั่วไป (Low)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">ทีม (Team)</label>
                                        <select
                                            value={formFilters.team}
                                            onChange={(e) => handleFilterChange('team', e.target.value)}
                                            className="w-full rounded-lg border-gray-300 focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white"
                                        >
                                            <option value="all">ทุกทีม</option>
                                            <option value="Hardware">ทีม Hardware</option>
                                            <option value="Network">ทีม Network</option>
                                            <option value="Software">ทีม Software</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">ค้นหา (Search)</label>
                                        <input
                                            type="text"
                                            value={formFilters.search}
                                            onFocus={() => setIsTyping(true)}
                                            onBlur={() => setIsTyping(false)}
                                            onChange={(e) => handleFilterChange('search', e.target.value)}
                                            placeholder="ค้นหา Ticket, ผู้แจ้ง..."
                                            className="w-full rounded-lg border-gray-300 focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white leading-normal"
                                        />
                                    </div>

                                    <div className="flex items-end gap-2">
                                        <button
                                            type="submit"
                                            className="w-full bg-indigo-600 border border-transparent text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm cursor-pointer"
                                        >
                                            กรองข้อมูล
                                        </button>
                                        <button
                                            type="button"
                                            onClick={handleClearFilters}
                                            className="w-full text-center bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors shadow-sm cursor-pointer"
                                        >
                                            ล้างค่า
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {/* Mobile Card View */}
                        <div className="md:hidden space-y-4 p-4">
                            {cases.data && cases.data.length > 0 ? (
                                cases.data.map((caseItem) => (
                                    <a
                                        key={caseItem.id}
                                        href={route('tickets.show', caseItem.id)}
                                        className="block bg-white border border-gray-200 rounded-xl shadow-sm p-4 hover:border-indigo-300 hover:shadow-md transition-all relative overflow-hidden"
                                    >
                                        <div className="flex justify-between items-start mb-2">
                                            <span className="text-sm font-bold text-indigo-600">#{caseItem.ticket_no}</span>
                                            <div className="flex flex-col items-end gap-1">
                                                {getStatusBadge(caseItem.status, caseItem.status_label)}
                                                {caseItem.is_overdue && (
                                                    <span className="px-2 py-0.5 inline-flex items-center gap-1 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700 animate-pulse">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-rose-500" />
                                                        เกิน SLA
                                                    </span>
                                                )}
                                                {caseItem.is_near_overdue && !caseItem.is_overdue && (
                                                    <span className="px-2 py-0.5 inline-flex items-center gap-1 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 animate-pulse">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-amber-500" />
                                                        ใกล้หมด SLA
                                                    </span>
                                                )}
                                                {caseItem.task2_status_label && (
                                                    <span className={`px-2 py-0.5 inline-flex text-[10px] leading-4 font-bold rounded-full shadow-sm ${caseItem.task2_status_color}`}>
                                                        {caseItem.task2_status_label}
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <h4 className="text-base font-semibold text-gray-900 mb-1 leading-tight">{caseItem.title}</h4>
                                        <div className="text-xs text-gray-500 mb-3">
                                            โดย: {caseItem.requester_name} ({caseItem.department || '-'})
                                        </div>

                                        <div className="flex justify-between items-center text-xs mt-3 pt-3 border-t border-gray-100">
                                            {getPriorityBadge(caseItem.priority, caseItem.priority_label)}
                                            <span className="flex items-center gap-1 text-gray-400">
                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {formatRelativeTime(caseItem.created_at)}
                                            </span>
                                        </div>
                                    </a>
                                ))
                            ) : (
                                <div className="text-center py-10">
                                    <svg className="h-10 w-10 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p className="text-sm font-medium text-gray-500">ไม่พบใบงานแจ้งซ่อมในขณะนี้</p>
                                </div>
                            )}
                        </div>

                        {/* Desktop Table View */}
                        <div className="hidden md:block overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50/70">
                                    <tr>
                                        <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Ticket No
                                        </th>
                                        <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            ข้อมูลผู้แจ้ง & ปัญหา
                                        </th>
                                        <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            ความเร่งด่วน
                                        </th>
                                        <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            ทีมที่รับผิดชอบ
                                        </th>
                                        <th scope="col" className="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            สถานะ
                                        </th>
                                        <th scope="col" className="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            จัดการ
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 bg-white">
                                    {cases.data && cases.data.length > 0 ? (
                                        cases.data.map((caseItem) => (
                                            <tr key={caseItem.id} className="hover:bg-slate-50 transition-colors duration-150">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <a
                                                        href={route('tickets.show', caseItem.id)}
                                                        className="text-sm font-bold text-indigo-600 hover:text-indigo-900 hover:underline"
                                                    >
                                                        #{caseItem.ticket_no}
                                                    </a>
                                                    <div className="text-xs text-gray-400 mt-0.5">
                                                        {formatRelativeTime(caseItem.created_at)}
                                                    </div>
                                                </td>

                                                <td className="px-6 py-4">
                                                    <div className="text-sm font-semibold text-gray-900">{caseItem.title}</div>
                                                    <div className="text-xs text-gray-500 mt-0.5">
                                                        โดย: {caseItem.requester_name} ({caseItem.department || '-'})
                                                    </div>
                                                </td>

                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {getPriorityBadge(caseItem.priority, caseItem.priority_label)}
                                                </td>

                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                                    {caseItem.escalated_to_team ? (
                                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                            ทีม {caseItem.escalated_to_team}
                                                        </span>
                                                    ) : caseItem.status === 'pending' ? (
                                                        <span className="text-gray-400 italic text-xs">ยังไม่ระบุ</span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-50 text-gray-700 border border-gray-200">
                                                            Helpdesk (Tier 1)
                                                        </span>
                                                    )}
                                                </td>

                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {getStatusBadge(caseItem.status, caseItem.status_label)}
                                                    {caseItem.is_overdue && (
                                                        <div className="mt-1">
                                                            <span className="px-2 py-0.5 inline-flex items-center gap-1 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700 animate-pulse">
                                                                <span className="w-1.5 h-1.5 rounded-full bg-rose-500" />
                                                                เกิน SLA
                                                            </span>
                                                        </div>
                                                    )}
                                                    {caseItem.is_near_overdue && !caseItem.is_overdue && (
                                                        <div className="mt-1">
                                                            <span className="px-2 py-0.5 inline-flex items-center gap-1 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 animate-pulse">
                                                                <span className="w-1.5 h-1.5 rounded-full bg-amber-500" />
                                                                ใกล้หมด SLA
                                                            </span>
                                                        </div>
                                                    )}
                                                    {caseItem.task2_status_label && (
                                                        <div className="mt-1">
                                                            <span className={`px-2.5 py-0.5 inline-flex text-[10px] leading-4 font-bold rounded-full shadow-sm ${caseItem.task2_status_color}`}>
                                                                {caseItem.task2_status_label}
                                                            </span>
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <a
                                                        href={route('tickets.show', caseItem.id)}
                                                        className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                                                    >
                                                        ดูรายละเอียด
                                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </a>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-12 text-center text-gray-500">
                                                <div className="flex flex-col items-center">
                                                    <svg className="h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                    <p className="text-sm font-medium">ไม่พบใบงานแจ้งซ่อมในขณะนี้</p>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {cases.links && cases.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
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
                                            className={`px-3 py-1.5 text-xs rounded-lg border transition cursor-pointer ${
                                                link.active
                                                    ? 'bg-indigo-600 text-white border-indigo-600 font-bold shadow-sm'
                                                    : link.url
                                                    ? 'bg-white text-gray-700 hover:bg-gray-100 border-gray-200'
                                                    : 'bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed'
                                            }`}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                </div>
            </div>
        </>
    );
}
