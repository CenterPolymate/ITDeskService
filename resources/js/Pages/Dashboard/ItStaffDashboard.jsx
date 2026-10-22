import React, { useState, useEffect, useRef } from 'react';
import { Head, router, Link, usePage } from '@inertiajs/react';
import {
    sendBrowserNotification,
    isTicketViewed,
    markTicketAsViewed,
} from '@/Utils/webNotification';
import SortableHeader from '@/Components/SortableHeader';

export default function ItStaffDashboard({ cases, stats, teams = [], technician_workload = null, filters = {} }) {
    const { auth, flash } = usePage().props;
    const userRole = auth.user.role;
    const isManager = ['manager', 'manager_software', 'manager_network_hardware'].includes(userRole);
    const isManagerOrAdmin = ['manager', 'manager_software', 'manager_network_hardware', 'administrator'].includes(userRole);

    const availableTeams = teams && teams.length > 0 ? teams : [
        { id: 1, code: 'HW', name: 'Hardware' },
        { id: 2, code: 'NET', name: 'Network' },
        { id: 3, code: 'SW', name: 'Software' },
    ];

    const activeTabFromFilter = filters.status?.startsWith('task2') || filters.manager_tab === 'task2' ? 'task2' : 'task1';
    const [managerTab, setManagerTab] = useState(activeTabFromFilter);

    const getInitialStatus = (tab, statusFilter) => {
        if (tab === 'task2') {
            return statusFilter?.startsWith('task2') ? statusFilter : 'task2_all';
        }
        return statusFilter?.startsWith('task2') ? 'not_closed' : (statusFilter || 'not_closed');
    };

    const [formFilters, setFormFilters] = useState({
        status: getInitialStatus(activeTabFromFilter, filters.status),
        priority: filters.priority || 'all',
        team: filters.team || 'all',
        technician_id: filters.technician_id || 'all',
        tier2_tab: filters.tier2_tab || 'all',
        search: filters.search || '',
    });

    useEffect(() => {
        const nextTab = filters.status?.startsWith('task2') || filters.manager_tab === 'task2' ? 'task2' : 'task1';
        setManagerTab(nextTab);
        setFormFilters(prev => ({
            ...prev,
            status: getInitialStatus(nextTab, filters.status),
            priority: filters.priority || 'all',
            team: filters.team || 'all',
            technician_id: filters.technician_id || 'all',
            tier2_tab: filters.tier2_tab || 'all',
            search: filters.search || '',
        }));
    }, [filters.status, filters.manager_tab, filters.priority, filters.team, filters.technician_id, filters.tier2_tab, filters.search]);

    const [isTyping, setIsTyping] = useState(false);
    const [highlightedTicketIds, setHighlightedTicketIds] = useState(new Set());
    const knownUrgentIdsRef = useRef(null);
    const knownTicketsMapRef = useRef(null);

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

    // Watch for new tickets and status changes during polling
    useEffect(() => {
        const ticketList = cases?.data || (Array.isArray(cases) ? cases : []);

        // First load: memorize existing tickets and statuses
        if (knownTicketsMapRef.current === null) {
            if (ticketList.length > 0) {
                knownTicketsMapRef.current = new Map(ticketList.map(c => [c.id, c.status]));
            }
            return;
        }

        // On subsequent polls, check for new tickets or status updates
        ticketList.forEach(t => {
            const isKnown = knownTicketsMapRef.current.has(t.id);
            const prevStatus = knownTicketsMapRef.current.get(t.id);

            // Check if ticket is ACTUALLY newly created (e.g. within 10 minutes)
            const createdAtTime = t.created_at ? new Date(t.created_at).getTime() : 0;
            const isRecent = createdAtTime > 0 && (Date.now() - createdAtTime) < (10 * 60 * 1000);
            const alreadyViewed = isTicketViewed(t.id, auth.user?.id);

            if (!isKnown) {
                // ONLY notify if it is a genuinely recent ticket that has not been viewed yet!
                if (isRecent && !alreadyViewed) {
                    let title = `🔔 มีเคสใหม่เข้ามา: #${t.ticket_no}`;
                    let body = `${t.title || 'เคสใหม่'}\nผู้แจ้ง: ${t.requester_name || '-'} (${t.department || '-'})${t.actual_user_name ? ` (แจ้งแทน: ${t.actual_user_name})` : ''}`;

                    if (t.priority === 'urgent') {
                        title = `🚨 เคสด่วนที่สุดใหม่: #${t.ticket_no}`;
                    } else if (userRole === 'technician' || userRole.startsWith('team_')) {
                        title = `🛠️ มีเคสใหม่ส่งถึงทีมคุณ: #${t.ticket_no}`;
                    } else if (userRole === 'helpdesk') {
                        title = `📥 มีเคสใหม่รอคัดกรอง (Triage): #${t.ticket_no}`;
                    } else if (isManager) {
                        title = `📋 มีเคสใหม่ในระบบ: #${t.ticket_no}`;
                    }

                    sendBrowserNotification(title, {
                        body,
                        tag: `staff-ticket-new-${t.id}`,
                        url: route('tickets.show', t.id),
                        ticket_no: t.ticket_no,
                        ticket_id: t.id,
                        priority: t.priority,
                        type: t.priority === 'urgent' ? 'urgent' : 'new',
                        onClick: () => {
                            markTicketAsViewed(t.id, auth.user?.id);
                            window.location.href = route('tickets.show', t.id);
                        },
                    });

                    // Highlight newly arrived ticket in table and card view for 30s
                    setHighlightedTicketIds(prev => new Set(prev).add(t.id));
                    setTimeout(() => {
                        setHighlightedTicketIds(prev => {
                            const next = new Set(prev);
                            next.delete(t.id);
                            return next;
                        });
                    }, 30000);
                }
            } else if (prevStatus && prevStatus !== t.status) {
                // Ticket status changed
                if (t.status === 'resolved') {
                    sendBrowserNotification(`🎉 เคส #${t.ticket_no} ส่งมอบงานแล้ว`, {
                        body: `รอผู้แจ้งตรวจสอบและกดยืนยันรับงาน`,
                        tag: `staff-ticket-status-${t.id}-${t.status}`,
                        url: route('tickets.show', t.id),
                        ticket_no: t.ticket_no,
                        ticket_id: t.id,
                        type: 'resolved',
                        onClick: () => {
                            markTicketAsViewed(t.id, auth.user?.id);
                            window.location.href = route('tickets.show', t.id);
                        },
                    });
                }

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

            knownTicketsMapRef.current.set(t.id, t.status);
        });
    }, [cases, userRole]);

    // Live polling updates every 15 seconds (paused if user is typing or focusing on inputs)
    useEffect(() => {
        const interval = setInterval(() => {
            if (!isTyping) {
                router.reload({
                    only: ['cases', 'stats', 'technician_workload'],
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

    const currentSort = filters.sort || 'created_at';
    const currentDirection = filters.direction || 'desc';

    const handleSort = (field) => {
        let nextDirection = 'asc';
        if (currentSort === field) {
            nextDirection = currentDirection === 'asc' ? 'desc' : 'asc';
        } else {
            nextDirection = (field === 'created_at' || field === 'ticket_no' || field === 'priority') ? 'desc' : 'asc';
        }
        handleSortDirect(field, nextDirection);
    };

    const handleSortDirect = (field, direction) => {
        router.get(
            route('dashboard'),
            {
                status: formFilters.status !== 'all' ? formFilters.status : undefined,
                priority: formFilters.priority !== 'all' ? formFilters.priority : undefined,
                team: formFilters.team !== 'all' ? formFilters.team : undefined,
                technician_id: formFilters.technician_id !== 'all' ? formFilters.technician_id : undefined,
                tier2_tab: formFilters.tier2_tab !== 'all' ? formFilters.tier2_tab : undefined,
                search: formFilters.search || undefined,
                manager_tab: managerTab || undefined,
                view: filters.view || undefined,
                sort: field,
                direction: direction,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleManagerTabSwitch = (tabKey) => {
        setManagerTab(tabKey);
        const targetStatus = tabKey === 'task2' ? 'task2_all' : 'not_closed';
        setFormFilters(prev => ({ ...prev, status: targetStatus }));
        router.get(
            route('dashboard'),
            {
                status: targetStatus,
                priority: formFilters.priority !== 'all' ? formFilters.priority : undefined,
                team: formFilters.team !== 'all' ? formFilters.team : undefined,
                technician_id: formFilters.technician_id !== 'all' ? formFilters.technician_id : undefined,
                tier2_tab: formFilters.tier2_tab !== 'all' ? formFilters.tier2_tab : undefined,
                search: formFilters.search || undefined,
                manager_tab: tabKey,
                view: filters.view || undefined,
                sort: currentSort !== 'created_at' ? currentSort : undefined,
                direction: currentDirection !== 'desc' ? currentDirection : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleFilterChange = (key, value) => {
        setFormFilters(prev => ({ ...prev, [key]: value }));
        if (key === 'status') {
            if (value.startsWith('task2')) {
                setManagerTab('task2');
            } else {
                setManagerTab('task1');
            }
        }
    };

    const handleTier2TabChange = (tabKey) => {
        setFormFilters(prev => ({ ...prev, tier2_tab: tabKey }));
        router.get(
            route('dashboard'),
            {
                status: formFilters.status !== 'all' ? formFilters.status : undefined,
                priority: formFilters.priority !== 'all' ? formFilters.priority : undefined,
                team: formFilters.team !== 'all' ? formFilters.team : undefined,
                technician_id: formFilters.technician_id !== 'all' ? formFilters.technician_id : undefined,
                tier2_tab: tabKey !== 'all' ? tabKey : undefined,
                search: formFilters.search || undefined,
                manager_tab: managerTab || undefined,
                view: filters.view || undefined,
                sort: currentSort !== 'created_at' ? currentSort : undefined,
                direction: currentDirection !== 'desc' ? currentDirection : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        router.get(
            route('dashboard'),
            {
                status: formFilters.status !== 'all' ? formFilters.status : undefined,
                priority: formFilters.priority !== 'all' ? formFilters.priority : undefined,
                team: formFilters.team !== 'all' ? formFilters.team : undefined,
                technician_id: formFilters.technician_id !== 'all' ? formFilters.technician_id : undefined,
                tier2_tab: formFilters.tier2_tab !== 'all' ? formFilters.tier2_tab : undefined,
                search: formFilters.search || undefined,
                manager_tab: managerTab || undefined,
                view: filters.view || undefined,
                sort: currentSort !== 'created_at' ? currentSort : undefined,
                direction: currentDirection !== 'desc' ? currentDirection : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleClearFilters = () => {
        const clearedStatus = managerTab === 'task2' ? 'task2_all' : 'not_closed';
        const cleared = { status: clearedStatus, priority: 'all', team: 'all', technician_id: 'all', tier2_tab: 'all', search: '' };
        setFormFilters(cleared);
        router.get(
            route('dashboard'),
            {
                status: clearedStatus,
                manager_tab: managerTab,
                view: filters.view || undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleQuickFilter = (statusKey) => {
        const targetTab = statusKey.startsWith('task2') ? 'task2' : 'task1';
        setManagerTab(targetTab);
        setFormFilters(prev => ({ ...prev, status: statusKey }));
        router.get(
            route('dashboard'),
            {
                status: statusKey !== 'all' ? statusKey : undefined,
                priority: formFilters.priority !== 'all' ? formFilters.priority : undefined,
                team: formFilters.team !== 'all' ? formFilters.team : undefined,
                technician_id: formFilters.technician_id !== 'all' ? formFilters.technician_id : undefined,
                tier2_tab: formFilters.tier2_tab !== 'all' ? formFilters.tier2_tab : undefined,
                search: formFilters.search || undefined,
                manager_tab: targetTab,
                view: filters.view || undefined,
                sort: currentSort !== 'created_at' ? currentSort : undefined,
                direction: currentDirection !== 'desc' ? currentDirection : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );

        // Smooth scroll to table
        const el = document.getElementById('tickets-table');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
        }
    };

    const handleTechnicianFilter = (techId) => {
        const newTechId = formFilters.technician_id === String(techId) ? 'all' : String(techId);
        setFormFilters(prev => ({ ...prev, technician_id: newTechId }));
        router.get(
            route('dashboard'),
            {
                status: formFilters.status !== 'all' ? formFilters.status : undefined,
                priority: formFilters.priority !== 'all' ? formFilters.priority : undefined,
                team: formFilters.team !== 'all' ? formFilters.team : undefined,
                technician_id: newTechId !== 'all' ? newTechId : undefined,
                tier2_tab: formFilters.tier2_tab !== 'all' ? formFilters.tier2_tab : undefined,
                search: formFilters.search || undefined,
                manager_tab: managerTab || undefined,
                view: filters.view || undefined,
                sort: currentSort !== 'created_at' ? currentSort : undefined,
                direction: currentDirection !== 'desc' ? currentDirection : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const renderSlaStatus = (caseItem) => {
        if (['closed', 'cancelled'].includes(caseItem.status)) {
            return null;
        }

        if (caseItem.status === 'resolved') {
            if (caseItem.sla_due_at && caseItem.resolved_at) {
                const resolvedTime = new Date(caseItem.resolved_at).getTime();
                const dueTime = new Date(caseItem.sla_due_at).getTime();
                if (resolvedTime > dueTime) {
                    return (
                        <span className="px-2 py-0.5 inline-flex items-center gap-1 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700">
                            เกิน SLA
                        </span>
                    );
                }
            }
            return (
                <span className="px-2 py-0.5 inline-flex items-center gap-1 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700">
                    ตาม SLA
                </span>
            );
        }

        if (!caseItem.sla_due_at) return null;

        const due = new Date(caseItem.sla_due_at);
        const now = new Date();
        const diffMs = due.getTime() - now.getTime();
        const isOverdue = diffMs < 0;
        const absMinutes = Math.floor(Math.abs(diffMs) / (1000 * 60));
        const hours = Math.floor(absMinutes / 60);
        const minutes = absMinutes % 60;
        const timeText = hours > 0 ? `${hours} ชม. ${minutes} นาที` : `${minutes} นาที`;

        if (isOverdue) {
            return (
                <span className="px-2 py-0.5 inline-flex items-center gap-1 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700 border border-rose-200 animate-pulse">
                    <span className="w-1.5 h-1.5 rounded-full bg-rose-500" />
                    เกิน {timeText}
                </span>
            );
        }

        if (absMinutes <= 60) {
            return (
                <span className="px-2 py-0.5 inline-flex items-center gap-1 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                    <span className="w-1.5 h-1.5 rounded-full bg-amber-500" />
                    เหลืออีก {timeText}
                </span>
            );
        }

        return (
            <span className="px-2 py-0.5 inline-flex items-center gap-1 text-[10px] font-medium rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                <svg className="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                เหลือ {timeText}
            </span>
        );
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

    const [currentTime] = useState(() => {
        return new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', hour12: false }) + ' น.';
    });

    const pipelineSteps = [
        {
            num: 1,
            key: 'pending',
            label: 'รอดำเนินการ / มอบหมาย',
            count: Number(stats.pending || 0),
            percent: Number(stats.percent_open_pending || 0),
            icon: (
                <svg className="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-17.5 0l1.75-8.25A2.25 2.25 0 016.02 3.5h11.96a2.25 2.25 0 012.21 1.75l1.75 8.25m-19.5 0v5.25a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-5.25" />
                </svg>
            ),
        },
        {
            num: 2,
            key: 'analyzing',
            label: 'สืบสภาพ / วิเคราะห์สาเหตุ',
            count: Number(stats.analyzing || 0),
            percent: Number(stats.percent_open_analyzing || 0),
            icon: (
                <svg className="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            ),
        },
        {
            num: 3,
            key: 'in_progress',
            label: 'ดำเนินการแก้ไข',
            count: Number(stats.in_progress || 0),
            percent: Number(stats.percent_open_in_progress || 0),
            icon: (
                <svg className="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.098-3.085.918l-7.468 7.469a2.121 2.121 0 01-3-3l7.469-7.468c.82-.821 1.009-2.009.918-3.085a4.5 4.5 0 014.484-4.884 4.5 4.5 0 013.566 5.566z" />
                </svg>
            ),
        },
        {
            num: 4,
            key: 'testing',
            label: 'ทดสอบ / ส่งมอบ',
            count: Number(stats.testing || 0),
            percent: Number(stats.percent_open_testing || 0),
            icon: (
                <svg className="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            ),
        },
    ];

    const maxStepCount = Math.max(...pipelineSteps.map(s => s.count));
    const bottleneckStepKey = maxStepCount > 0 ? pipelineSteps.find(s => s.count === maxStepCount)?.key : null;

    return (
        <>
            <Head title="แดชบอร์ดฝ่าย IT - ITDeskService" />

            <div className="min-h-screen pb-10 pt-3.5 bg-slate-50">
                <div id="dashboard-content" className="w-full px-4 sm:px-6 lg:px-8 mx-auto xl:max-w-[95%] space-y-4">

                    {/* Administrator Back to System Overview Banner */}
                    {userRole === 'administrator' && (
                        <div className="bg-slate-900 text-white px-4 py-2.5 rounded-xl shadow-xs flex items-center justify-between">
                            <div className="flex items-center gap-2.5">
                                <span className="px-2 py-0.5 rounded bg-indigo-600 text-white text-[11px] font-bold uppercase tracking-wider">
                                    โหมดการดำเนินงานซ่อม (IT Operations)
                                </span>
                                <span className="text-xs text-slate-300 hidden sm:inline">
                                    คุณกำลังดูภาพรวมการดำเนินงานของทีมฝ่าย IT ทั้งหมด
                                </span>
                            </div>
                            <Link
                                href={route('dashboard')}
                                className="bg-white hover:bg-slate-100 text-slate-900 text-xs font-bold px-3 py-1.5 rounded-lg shadow-xs transition"
                            >
                                ← กลับสู่ภาพรวมระบบ (System Overview)
                            </Link>
                        </div>
                    )}

                    {/* Flash Success */}
                    {flash?.success && (
                        <div className="bg-emerald-50 border border-emerald-400 text-emerald-800 px-4 py-2.5 rounded-xl shadow-xs flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <svg className="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span className="text-sm font-medium">{flash.success}</span>
                            </div>
                        </div>
                    )}

                    {/* Helpdesk Pending Triage Notification Banner */}
                    {userRole === 'helpdesk' && Number(stats.pending_triage_count) > 0 && (
                        <div className="bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl px-4 py-2.5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-2.5 border border-amber-400">
                            <div className="flex items-center gap-2.5">
                                <div className="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                                    <svg className="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 className="text-xs sm:text-sm font-bold">มีเคสใหม่รอคัดกรอง (Pending Triage) จำนวน {Number(stats.pending_triage_count).toLocaleString()} ใบ</h4>
                                    <p className="text-[11px] text-amber-100">โปรดตรวจสอบอาการปัญหา ระบุหมวดหมู่ ความเร่งด่วน และส่งต่อให้ทีมเฉพาะทาง</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={() => handleQuickFilter('pending')}
                                className="bg-white text-amber-800 hover:bg-amber-50 font-bold px-3.5 py-1.5 rounded-lg text-xs shadow-xs transition whitespace-nowrap cursor-pointer"
                            >
                                คัดกรองเคสใหม่ ({Number(stats.pending_triage_count).toLocaleString()})
                            </button>
                        </div>
                    )}

                    {/* Header Section with Manager/Admin Segmented Switch */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 className="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">สถานะการดำเนินงาน</h2>
                            <div className="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                <span>อัปเดตล่าสุด {currentTime}</span>
                                <span>·</span>
                                <button
                                    type="button"
                                    onClick={() => handleQuickFilter('all')}
                                    className="text-gray-600 underline hover:text-blue-600 transition cursor-pointer"
                                >
                                    ดูรายการทั้งหมด {Number(stats.total).toLocaleString()} เคส
                                </button>
                            </div>
                        </div>

                        {/* Manager / Admin Segmented Switch */}
                        {isManagerOrAdmin && (
                            <div className="flex items-center p-1 bg-slate-200/80 rounded-xl shadow-2xs self-start sm:self-auto">
                                <button
                                    type="button"
                                    onClick={() => handleManagerTabSwitch('task1')}
                                    className={`flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                                        managerTab === 'task1'
                                            ? 'bg-white text-slate-900 shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900'
                                    }`}
                                >
                                    <svg className="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>Task 1: งานซ่อม & ภาระงาน</span>
                                    <span className="px-1.5 py-0.2 rounded-full text-[10px] bg-indigo-50 text-indigo-700 font-bold">
                                        {Number(stats.open_total || 0)}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleManagerTabSwitch('task2')}
                                    className={`flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer ${
                                        managerTab === 'task2'
                                            ? 'bg-white text-slate-900 shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900'
                                    }`}
                                >
                                    <svg className="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>Task 2: ป้องกันปัญหาไม่ให้เกิดซ้ำ</span>
                                    {stats.task2_manager_review > 0 && (
                                        <span className="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-500 text-white font-bold animate-pulse">
                                            รอตรวจ {Number(stats.task2_manager_review)}
                                        </span>
                                    )}
                                </button>
                            </div>
                        )}
                    </div>

                    {/* Task 1 Container (Visible if not manager/admin OR if managerTab === 'task1') */}
                    {(!isManagerOrAdmin || managerTab === 'task1') && (
                        <div className="space-y-2.5">
                            {/* Merged Sleek Alert Banner (Active Overdue / Urgent) */}
                            {(Number(stats.sla_overdue) > 0 || Number(stats.urgent) > 0) && (
                            <div className="bg-[#8b181b] text-white rounded-xl px-4 py-2 sm:py-2.5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-2.5">
                                <div className="flex items-center gap-2.5 w-full sm:w-auto">
                                    <div className="w-5 h-5 rounded-full border-2 border-white/90 flex items-center justify-center shrink-0 text-white font-black text-[11px]">
                                        !
                                    </div>
                                    <span className="font-bold text-xs sm:text-sm tracking-wide">
                                        {Number(stats.sla_overdue) > 0 && Number(stats.urgent) > 0 ? (
                                            <>
                                                มี <span className="underline decoration-white/60">{Number(stats.sla_overdue).toLocaleString()} เคสเกิน SLA</span> และ <span className="underline decoration-white/60">{Number(stats.urgent).toLocaleString()} เคสด่วนที่สุด</span> ที่ต้องดำเนินการทันที
                                            </>
                                        ) : Number(stats.sla_overdue) > 0 ? (
                                            `มี ${Number(stats.sla_overdue).toLocaleString()} เคสเกิน SLA ต้องดำเนินการทันที`
                                        ) : (
                                            `พบ ${Number(stats.urgent).toLocaleString()} เคสระดับ "ด่วนที่สุด" ที่ต้องจัดการทันที`
                                        )}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2 w-full sm:w-auto shrink-0 justify-end">
                                    {Number(stats.urgent) > 0 && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setFormFilters(prev => ({ ...prev, priority: 'urgent', status: 'not_closed' }));
                                                router.get(route('dashboard'), { priority: 'urgent', status: 'not_closed' }, { preserveState: true, preserveScroll: true });
                                            }}
                                            className="flex-1 sm:flex-initial bg-red-700/90 hover:bg-red-700 text-white border border-red-400/40 font-bold px-3 py-1.5 rounded-lg text-xs shadow-xs transition active:scale-95 text-center cursor-pointer"
                                        >
                                            ดูเคสด่วน ({Number(stats.urgent).toLocaleString()})
                                        </button>
                                    )}
                                    {Number(stats.sla_overdue) > 0 && (
                                        <button
                                            type="button"
                                            onClick={() => handleQuickFilter('sla_overdue')}
                                            className="flex-1 sm:flex-initial bg-white text-[#8b181b] hover:bg-red-50 font-bold px-3.5 py-1.5 rounded-lg text-xs shadow-xs transition active:scale-95 text-center cursor-pointer"
                                        >
                                            ดูเคสที่เกิน SLA ({Number(stats.sla_overdue).toLocaleString()})
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* 3 Overview Cards Row (Compact Height: 105px) */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                            {/* 1. Open Cases */}
                            <div
                                onClick={() => handleQuickFilter('not_closed')}
                                className="bg-[#fcf8ee] border border-[#edd9b6] rounded-xl p-3 sm:px-4 sm:py-2.5 flex flex-col justify-between h-[100px] sm:h-[105px] shadow-2xs hover:shadow-sm transition cursor-pointer group"
                            >
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-bold text-amber-900/80">งานค้าง (ยังไม่ปิด)</span>
                                    <div className="w-7 h-7 rounded-lg bg-amber-200/70 text-amber-800 flex items-center justify-center shrink-0">
                                        <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div className="z-10 -my-0.5">
                                    <span className="text-3xl font-black text-slate-900 leading-none tracking-tight">
                                        {Number(stats.open_total || 0).toLocaleString()}
                                    </span>
                                </div>
                                <div className="z-10 text-[11px] text-amber-900/70 font-medium truncate">
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleQuickFilter('pending');
                                        }}
                                        className="hover:underline hover:text-amber-950 cursor-pointer"
                                    >
                                        ยังไม่มอบหมาย {Number(stats.pending || 0).toLocaleString()}
                                    </button>
                                    {' · '}
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleQuickFilter('active');
                                        }}
                                        className="hover:underline hover:text-amber-950 cursor-pointer"
                                    >
                                        กำลังทำ {Number((stats.analyzing || 0) + (stats.in_progress || 0) + (stats.testing || 0)).toLocaleString()}
                                    </button>
                                </div>
                            </div>

                            {/* 2. Overdue SLA */}
                            <div
                                onClick={() => handleQuickFilter('sla_overdue')}
                                className="bg-[#fff1f2] border border-[#fecdd3] rounded-xl p-3 sm:px-4 sm:py-2.5 flex flex-col justify-between h-[100px] sm:h-[105px] shadow-2xs hover:shadow-sm transition cursor-pointer group"
                            >
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-bold text-rose-700">เกิน SLA</span>
                                    <div className="w-7 h-7 rounded-lg bg-rose-200/80 text-rose-700 flex items-center justify-center shrink-0">
                                        <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                    </div>
                                </div>
                                <div className="z-10 -my-0.5">
                                    <span className="text-3xl font-black text-rose-700 leading-none tracking-tight">
                                        {Number(stats.sla_overdue || 0).toLocaleString()}
                                    </span>
                                </div>
                                <div className="z-10 flex items-center gap-1.5 text-[11px] font-semibold text-amber-800 truncate">
                                    <span className="text-amber-500">⚠</span>
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleQuickFilter('sla_warning');
                                        }}
                                        className="hover:underline hover:text-amber-950 cursor-pointer"
                                    >
                                        ใกล้ครบ SLA: {Number(stats.sla_warning || 0).toLocaleString()} เคส
                                    </button>
                                </div>
                            </div>

                            {/* 3. Closed Today */}
                            <div
                                onClick={() => handleQuickFilter('closed_today')}
                                className="bg-[#f0fdf4] border border-[#bbf7d0] rounded-xl p-3 sm:px-4 sm:py-2.5 flex flex-col justify-between h-[100px] sm:h-[105px] shadow-2xs hover:shadow-sm transition cursor-pointer group"
                            >
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-bold text-emerald-800">ปิดงานวันนี้</span>
                                    <div className="w-7 h-7 rounded-lg bg-emerald-200/80 text-emerald-700 flex items-center justify-center shrink-0">
                                        <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div className="z-10 -my-0.5">
                                    <span className="text-3xl font-black text-emerald-700 leading-none tracking-tight">
                                        {Number(stats.closed_today || 0).toLocaleString()}
                                    </span>
                                </div>
                                <div className="z-10 text-[11px] text-slate-500 font-medium truncate">
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleQuickFilter('cancelled');
                                        }}
                                        className="hover:underline hover:text-slate-800 cursor-pointer"
                                    >
                                        ยกเลิก {Number(stats.cancelled_today || stats.cancelled || 0).toLocaleString()} เคส
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Stepper Workflow Pipeline Card (Compact) */}
                        <div className="bg-white rounded-xl p-3.5 sm:p-4 border border-slate-200/90 shadow-2xs space-y-2.5">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-1 border-b border-slate-100 pb-2">
                                <h3 className="text-sm font-bold text-slate-800 tracking-tight">งานที่เปิดอยู่ แยกตามขั้นตอน</h3>
                                <span className="text-xs text-slate-400 font-medium">
                                    {Number(stats.open_total || 0).toLocaleString()} เคส · กดขั้นตอนเพื่อกรองรายการ
                                </span>
                            </div>

                            <div className="grid grid-cols-2 lg:flex lg:flex-row lg:items-center lg:justify-between gap-2.5">
                                {pipelineSteps.map((step, idx) => {
                                    const isBottleneck = step.key === bottleneckStepKey;
                                    return (
                                        <React.Fragment key={step.key}>
                                            <div
                                                onClick={() => handleQuickFilter(step.key)}
                                                className="flex-1 p-2 sm:p-2.5 rounded-xl hover:bg-slate-50/80 transition cursor-pointer flex flex-col group"
                                            >
                                                {/* Step Icon & Step Number */}
                                                <div className="flex items-center justify-between mb-1.5">
                                                    <div
                                                        className={`w-8 h-8 rounded-lg flex items-center justify-center transition-all shrink-0 ${
                                                            isBottleneck
                                                                ? 'bg-amber-600 text-white shadow-xs'
                                                                : 'bg-slate-100 text-slate-700 border border-slate-200/80 shadow-2xs group-hover:border-slate-300'
                                                        }`}
                                                    >
                                                        {step.icon}
                                                    </div>
                                                    <span className={`text-[10px] font-black font-mono px-1.5 py-0.5 rounded ${
                                                        isBottleneck ? 'bg-amber-100 text-amber-800' : 'text-slate-400 bg-slate-100/70'
                                                    }`}>
                                                        0{step.num}
                                                    </span>
                                                </div>

                                                {/* Label */}
                                                <p className="text-xs font-semibold text-slate-700 leading-snug line-clamp-1 mb-0.5">
                                                    {step.label}
                                                </p>

                                                {/* Count & % */}
                                                <div className="flex items-baseline gap-1 my-0.5">
                                                    <span className="text-lg sm:text-xl font-black text-slate-900 leading-none">
                                                        {step.count}
                                                    </span>
                                                    <span className="text-[11px] text-slate-400 font-medium">
                                                        เคส · {step.percent}%
                                                    </span>
                                                </div>

                                                {/* Progress Bar */}
                                                <div className="w-full h-1 bg-slate-100 rounded-full overflow-hidden mt-1">
                                                    <div
                                                        className={`h-full rounded-full transition-all duration-500 ${
                                                            isBottleneck ? 'bg-amber-600' : 'bg-slate-400'
                                                        }`}
                                                        style={{ width: `${Math.min(step.percent, 100)}%` }}
                                                    />
                                                </div>

                                                {/* Bottleneck Badge */}
                                                <div className="min-h-[20px] mt-1 flex items-center">
                                                    {isBottleneck && (
                                                        <span className="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#fef3c7] text-[#92400e] border border-amber-200 shadow-2xs">
                                                            งานกองมากสุด
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Arrow between steps (only on lg: desktop) */}
                                            {idx < pipelineSteps.length - 1 && (
                                                <div className="hidden lg:flex items-center text-slate-300 shrink-0 self-center -mt-4">
                                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                    </svg>
                                                </div>
                                            )}
                                        </React.Fragment>
                                    );
                                })}
                            </div>
                        </div>

                    {/* ภาระงานของทีมช่าง (Technician Workload) - For Tier 3 Managers & Administrator */}
                    {technician_workload && (
                        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            {/* Header */}
                            <div className="p-5 sm:p-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50/30">
                                <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <h3 className="text-lg font-bold text-slate-900">
                                                    ภาระงานของทีมช่าง (Technician Workload)
                                                </h3>
                                                <span className="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 border border-indigo-200">
                                                    {technician_workload.team_title}
                                                </span>
                                            </div>
                                            <p className="text-xs text-slate-500 mt-0.5">
                                                ติดตามสถานะช่างในทีมและจำนวนใบงานที่กำลังถือครองอยู่ (สืบสภาพ / กำลังแก้ไข)
                                            </p>
                                        </div>
                                    </div>

                                    {/* Summary Stats Badges */}
                                    <div className="flex flex-wrap items-center gap-2 self-stretch sm:self-auto">
                                        <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-medium border border-slate-200">
                                            <span className="text-slate-400">ช่างในทีม:</span>
                                            <span className="font-bold text-slate-900">{technician_workload.total_technicians} ท่าน</span>
                                        </div>
                                        <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-800 text-xs font-medium border border-indigo-200">
                                            <span className="text-indigo-400">งานที่กำลังถือครอง:</span>
                                            <span className="font-bold text-indigo-700">{technician_workload.total_active_tickets} ใบ</span>
                                        </div>
                                        {technician_workload.unassigned_queue_count > 0 && (
                                            <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 text-amber-800 text-xs font-medium border border-amber-200">
                                                <span className="text-amber-500 font-bold">รอช่างกดรับ:</span>
                                                <span className="font-bold text-amber-700">{technician_workload.unassigned_queue_count} ใบ</span>
                                            </div>
                                        )}
                                        {technician_workload.total_overdue_tickets > 0 && (
                                            <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-50 text-red-800 text-xs font-medium border border-red-200 animate-pulse">
                                                <span className="text-red-500">เกิน SLA:</span>
                                                <span className="font-bold text-red-700">{technician_workload.total_overdue_tickets} ใบ</span>
                                            </div>
                                        )}
                                        {formFilters.technician_id !== 'all' && (
                                            <button
                                                type="button"
                                                onClick={() => handleTechnicianFilter('all')}
                                                className="text-xs text-rose-600 hover:text-rose-800 underline font-semibold px-2 py-1"
                                            >
                                                ✕ ยกเลิกการกรองช่าง
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Unassigned Central Queue Notice */}
                            {technician_workload.unassigned_queue_count > 0 && (
                                <div className="mx-5 sm:mx-6 mt-4 p-3 bg-amber-50/90 border border-amber-200 rounded-xl flex items-center justify-between gap-3 text-xs text-amber-800">
                                    <div className="flex items-center gap-2">
                                        <svg className="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>
                                            มีใบงานในคิวส่วนกลางของทีมจำนวน <strong className="font-bold">{technician_workload.unassigned_queue_count}</strong> ใบ ที่รอช่างในทีมกดรับเพื่อเริ่มสืบสภาพ
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => handleQuickFilter('pending')}
                                        className="px-3 py-1 bg-white hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg font-bold shadow-2xs shrink-0 transition"
                                    >
                                        ดูคิวงาน
                                    </button>
                                </div>
                            )}

                            {/* Technician Cards */}
                            <div className="p-5 sm:p-6">
                                {technician_workload.technicians && technician_workload.technicians.length > 0 ? (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                        {technician_workload.technicians.map((tech) => {
                                            const isSelected = formFilters.technician_id === String(tech.id);
                                            return (
                                                <div
                                                    key={tech.id}
                                                    className={`rounded-2xl border p-4 transition-all duration-200 flex flex-col justify-between ${
                                                        isSelected
                                                            ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20 shadow-md'
                                                            : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm'
                                                    }`}
                                                >
                                                    {/* Technician Header */}
                                                    <div>
                                                        <div className="flex items-start justify-between gap-2 mb-3">
                                                            <div className="flex items-center gap-2.5 min-w-0">
                                                                <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-slate-700 to-indigo-800 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                                                    {tech.name.substring(0, 1)}
                                                                </div>
                                                                <div className="min-w-0">
                                                                    <h4 className="text-sm font-bold text-slate-900 truncate" title={tech.name}>
                                                                        {tech.name}
                                                                    </h4>
                                                                    <span className="text-[10px] font-semibold text-slate-500 block truncate">
                                                                        {tech.role_label}
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            {/* SLA Status Pill */}
                                                            {tech.sla_overdue_count > 0 ? (
                                                                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200 shrink-0">
                                                                    เกิน {tech.sla_overdue_count}
                                                                </span>
                                                            ) : tech.sla_warning_count > 0 ? (
                                                                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200 shrink-0">
                                                                    เตือน {tech.sla_warning_count}
                                                                </span>
                                                            ) : tech.total_active > 0 ? (
                                                                <span className="px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                                                    ปกติ
                                                                </span>
                                                            ) : (
                                                                <span className="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500 shrink-0">
                                                                    ว่าง
                                                                </span>
                                                            )}
                                                        </div>

                                                        {/* Ticket Count Breakdown */}
                                                        <div className="grid grid-cols-3 gap-1.5 p-2 bg-slate-50 rounded-xl mb-3 text-center">
                                                            <div>
                                                                <div className="text-[10px] text-slate-400 font-medium">สืบสภาพ</div>
                                                                <div className="text-xs font-bold text-purple-700 mt-0.5">{tech.analyzing_count}</div>
                                                            </div>
                                                            <div className="border-x border-slate-200">
                                                                <div className="text-[10px] text-slate-400 font-medium">แก้ไขอยู่</div>
                                                                <div className="text-xs font-bold text-blue-700 mt-0.5">{tech.in_progress_count}</div>
                                                            </div>
                                                            <div>
                                                                <div className="text-[10px] text-slate-400 font-medium">เสร็จวันนี้</div>
                                                                <div className="text-xs font-bold text-emerald-700 mt-0.5">{tech.resolved_today_count}</div>
                                                            </div>
                                                        </div>

                                                        {/* Total active highlight */}
                                                        <div className="flex items-center justify-between text-xs px-1 mb-3">
                                                            <span className="text-slate-500">งานค้างที่ถืออยู่ทั้งหมด:</span>
                                                            <span className={`text-sm font-black ${tech.total_active > 3 ? 'text-rose-600' : tech.total_active > 0 ? 'text-indigo-600' : 'text-slate-400'}`}>
                                                                {tech.total_active} <span className="text-[11px] font-normal text-slate-400">ใบ</span>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    {/* Filter Button */}
                                                    <button
                                                        type="button"
                                                        onClick={() => handleTechnicianFilter(tech.id)}
                                                        className={`w-full py-2 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs ${
                                                            isSelected
                                                                ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                                                                : 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200'
                                                        }`}
                                                    >
                                                        {isSelected ? (
                                                            <>
                                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                <span>กำลังกรองช่างคนนี้</span>
                                                            </>
                                                        ) : (
                                                            <span>ดูกรองเฉพาะช่างคนนี้</span>
                                                        )}
                                                    </button>
                                                </div>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <div className="text-center py-8 text-slate-400 text-sm">
                                        ยังไม่มีรายชื่อช่างในความรับผิดชอบของทีมนี้
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Task 2 Section (Visible for manager/admin when managerTab === 'task2', OR for technicians) */}
            {userRole !== 'helpdesk' && (!isManagerOrAdmin || managerTab === 'task2') && (
                <div className="space-y-4">
                    {/* Manager Approval Banner (Task 2) */}
                    {isManagerOrAdmin && stats.task2_manager_review > 0 && (
                        <div className="bg-gradient-to-r from-amber-600 to-amber-500 rounded-2xl p-5 shadow-sm flex flex-col sm:flex-row items-center justify-between text-white overflow-hidden relative border border-amber-400">
                            <div className="flex items-center gap-4 z-10 w-full sm:w-auto mb-3 sm:mb-0">
                                <div className="bg-white/20 p-2.5 rounded-xl animate-pulse shrink-0">
                                    <svg className="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-base sm:text-lg font-bold tracking-wide">แจ้งเตือน: งานรอหัวหน้าตรวจสอบ</h3>
                                    <p className="text-amber-100 text-xs sm:text-sm mt-0.5">
                                        พบใบงานสถานะ "ปิดใบงานแล้ว" จำนวน <span className="font-black px-1.5 py-0.5 bg-white/20 rounded mx-1">{Number(stats.task2_manager_review).toLocaleString()}</span> รายการที่รอการตรวจสอบมาตรการป้องกัน
                                    </p>
                                </div>
                            </div>
                            <button
                                onClick={() => handleQuickFilter('task2_manager_review')}
                                className="w-full sm:w-auto bg-white text-amber-600 font-bold py-2 px-5 rounded-xl shadow-sm hover:bg-gray-50 transition cursor-pointer text-sm shrink-0"
                            >
                                ดูเคสรอตรวจสอบทั้งหมด
                            </button>
                        </div>
                    )}

                    <div className="flex items-center pt-2">
                        <div className="bg-teal-600 w-1.5 h-6 rounded-full mr-3" />
                        <h2 className="text-xl font-bold text-gray-800">
                            Task 2: การป้องกันปัญหาไม่ให้เกิดซ้ำ (Preventive Action)
                        </h2>
                    </div>

                    <div className={`grid grid-cols-2 md:grid-cols-3 ${isManagerOrAdmin ? 'lg:grid-cols-6' : 'lg:grid-cols-5'} gap-4 auto-rows-fr`}>
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
                        {isManagerOrAdmin && (
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
                            <div className="z-10 mt-2.5 pt-2 border-t border-teal-100/80 text-[11px] text-slate-500 font-medium truncate">
                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        handleQuickFilter('task2_cancelled');
                                    }}
                                    className="hover:underline hover:text-slate-800 cursor-pointer flex items-center gap-1"
                                >
                                    <span>ยกเลิก P-CAR {Number(stats.task2_cancelled || 0).toLocaleString()} เคส</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

                    {/* Table Section */}
                    <div id="tickets-table" className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-6 scroll-mt-6">
                        {/* Tier 2 Quick Tabs for Technicians */}
                        {(userRole === 'technician' || userRole.startsWith('team_')) && (
                            <div className="flex items-center gap-2 px-6 pt-4 border-b border-gray-200 bg-white overflow-x-auto">
                                <button
                                    type="button"
                                    onClick={() => handleTier2TabChange('my_work')}
                                    className={`pb-3 px-3 text-xs sm:text-sm font-bold border-b-2 flex items-center gap-2 cursor-pointer transition whitespace-nowrap ${
                                        formFilters.tier2_tab === 'my_work'
                                            ? 'border-indigo-600 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700'
                                    }`}
                                >
                                    <span>💼 งานของฉัน</span>
                                    <span className={`px-2 py-0.5 rounded-full text-[11px] font-bold ${
                                        formFilters.tier2_tab === 'my_work' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600'
                                    }`}>
                                        {Number(stats.my_work_count || 0)}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleTier2TabChange('team_queue')}
                                    className={`pb-3 px-3 text-xs sm:text-sm font-bold border-b-2 flex items-center gap-2 cursor-pointer transition whitespace-nowrap ${
                                        formFilters.tier2_tab === 'team_queue'
                                            ? 'border-indigo-600 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700'
                                    }`}
                                >
                                    <span>📥 คิวงานรอช่างรับ</span>
                                    <span className={`px-2 py-0.5 rounded-full text-[11px] font-bold ${
                                        formFilters.tier2_tab === 'team_queue' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'
                                    }`}>
                                        {Number(stats.team_queue_count || 0)}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleTier2TabChange('all')}
                                    className={`pb-3 px-3 text-xs sm:text-sm font-bold border-b-2 flex items-center gap-2 cursor-pointer transition whitespace-nowrap ${
                                        formFilters.tier2_tab === 'all' || !formFilters.tier2_tab
                                            ? 'border-indigo-600 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700'
                                    }`}
                                >
                                    <span>🌐 งานทั้งหมดในทีม</span>
                                    <span className="px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600">
                                        {Number(stats.open_total || 0)}
                                    </span>
                                </button>
                            </div>
                        )}

                        <div className="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div>
                                    <h3 className="text-lg font-bold text-gray-900">
                                        {managerTab === 'task2' ? 'รายการเคสมาตรการป้องกันปัญหา (Task 2)' : 'รายการแจ้งซ่อมที่ต้องจัดการ'}
                                    </h3>
                                    <p className="text-xs text-gray-500 mt-0.5">
                                        {managerTab === 'task2' ? 'ค้นหาและติดตามเคส P-CAR และการป้องกันปัญหาไม่ให้เกิดซ้ำ' : 'ค้นหาและกรองใบงานตามความเร่งด่วน ทีม และสถานะ'}
                                    </p>
                                </div>
                                <span className="text-xs font-semibold px-2.5 py-1 bg-white text-gray-600 rounded-lg border border-gray-200">
                                    พบทั้งหมด {cases.total || 0} รายการ
                                </span>
                            </div>

                            {/* Comprehensive Filters */}
                            <form onSubmit={handleSearchSubmit} className="w-full mt-4 p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
                                <div className={`grid grid-cols-1 md:grid-cols-2 ${technician_workload?.technicians?.length > 0 ? 'lg:grid-cols-6' : 'lg:grid-cols-5'} gap-3`}>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">
                                            {managerTab === 'task2' ? 'สถานะมาตรการ (Task 2 Status)' : 'สถานะใบงาน (Status)'}
                                        </label>
                                        <select
                                            value={
                                                managerTab === 'task2'
                                                    ? (formFilters.status?.startsWith('task2') ? formFilters.status : 'task2_all')
                                                    : (formFilters.status?.startsWith('task2') ? 'not_closed' : formFilters.status)
                                            }
                                            onChange={(e) => handleFilterChange('status', e.target.value)}
                                            className="w-full h-[38px] rounded-lg border-gray-300 focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white"
                                        >
                                            {managerTab === 'task2' ? (
                                                <>
                                                    <option value="task2_all">เคส Task 2 ทั้งหมด (ทุกสถานะ)</option>
                                                    <option value="task2_manager_review">รอหัวหน้าตรวจมาตรการป้องกัน</option>
                                                    <option value="task2_assigned">รอดำเนินการป้องกัน</option>
                                                    <option value="task2_in_progress">กำลังดำเนินการป้องกัน</option>
                                                    <option value="task2_pending_review">กำลังตรวจสอบปิดมาตรการ</option>
                                                    <option value="task2_completed">ปิดมาตรการป้องกันแล้ว</option>
                                                    <option value="task2_cancelled">มาตรการป้องกันถูกยกเลิก</option>
                                                </>
                                            ) : (
                                                <>
                                                    <option value="not_closed">ยังไม่ปิดใบงาน (ค่าเริ่มต้น)</option>
                                                    <option value="all">ทุกสถานะของ Task 1 (รวมปิดงานแล้ว)</option>
                                                    <option value="pending">รอดำเนินการ / มอบหมาย</option>
                                                    <option value="analyzing">สืบสภาพ / วิเคราะห์สาเหตุ</option>
                                                    <option value="in_progress">ดำเนินการแก้ไข</option>
                                                    <option value="active">กำลังทำ (Assigned/Analyzing/In Progress)</option>
                                                    <option value="resolved">รอผู้แจ้งกดรับงาน</option>
                                                    <option value="completed">แก้ไขเสร็จ / ปิดใบงาน</option>
                                                    <option value="closed_today">ปิดงานวันนี้</option>
                                                    <option value="sla_warning">⏰ ใกล้หมดเวลา SLA (&lt; 1 ชม.)</option>
                                                    <option value="sla_overdue">⚠️ เกินกำหนดเวลา SLA แล้ว</option>
                                                    <option value="breached">แก้ไขเสร็จเกิน SLA</option>
                                                </>
                                            )}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">ความเร่งด่วน (Priority)</label>
                                        <select
                                            value={formFilters.priority}
                                            onChange={(e) => handleFilterChange('priority', e.target.value)}
                                            className="w-full h-[38px] rounded-lg border-gray-300 focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white"
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
                                            className="w-full h-[38px] rounded-lg border-gray-300 focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white"
                                        >
                                            <option value="all">ทุกทีม (All Teams)</option>
                                            {availableTeams.map((t) => (
                                                <option key={t.id || t.name} value={t.name}>
                                                    [{t.code}] {t.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    {technician_workload?.technicians?.length > 0 && (
                                        <div>
                                            <label className="block text-xs font-medium text-gray-600 mb-1">ช่างในทีม (Technician)</label>
                                            <select
                                                value={formFilters.technician_id}
                                                onChange={(e) => handleFilterChange('technician_id', e.target.value)}
                                                className="w-full h-[38px] rounded-lg border-gray-300 focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white"
                                            >
                                                <option value="all">ช่างทุกคนในทีม</option>
                                                {technician_workload.technicians.map((t) => (
                                                    <option key={t.id} value={t.id}>
                                                        {t.name} ({t.total_active} ใบ)
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    )}

                                    <div>
                                        <label className="block text-xs font-medium text-gray-600 mb-1">ค้นหา (Search)</label>
                                        <input
                                            type="text"
                                            value={formFilters.search}
                                            onFocus={() => setIsTyping(true)}
                                            onBlur={() => setIsTyping(false)}
                                            onChange={(e) => handleFilterChange('search', e.target.value)}
                                            placeholder="ค้นหา Ticket, ผู้แจ้ง..."
                                            className="w-full h-[38px] rounded-lg border-gray-300 focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white leading-normal"
                                        />
                                    </div>

                                    <div>
                                        <label className="hidden sm:block text-xs font-medium text-transparent mb-1 select-none pointer-events-none">&nbsp;</label>
                                        <div className="flex items-center gap-2">
                                            <button
                                                type="submit"
                                                className="flex-1 h-[38px] inline-flex items-center justify-center bg-indigo-600 border border-transparent text-white px-2.5 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm cursor-pointer whitespace-nowrap"
                                            >
                                                กรองข้อมูล
                                            </button>
                                            <button
                                                type="button"
                                                onClick={handleClearFilters}
                                                className="h-[38px] inline-flex items-center justify-center bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors shadow-sm cursor-pointer whitespace-nowrap shrink-0"
                                            >
                                                ล้างค่า
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {formFilters.technician_id !== 'all' && (
                                    <div className="mt-3 pt-3 border-t border-gray-100 flex items-center gap-2 text-xs">
                                        <span className="text-gray-500 font-medium">กำลังกรองเฉพาะใบงานของช่าง:</span>
                                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-100 text-indigo-800 font-bold border border-indigo-200 shadow-2xs">
                                            <span>👤 {technician_workload?.technicians?.find(t => String(t.id) === String(formFilters.technician_id))?.name || `ID #${formFilters.technician_id}`}</span>
                                            <button
                                                type="button"
                                                onClick={() => handleTechnicianFilter('all')}
                                                className="text-indigo-600 hover:text-indigo-900 ml-1 font-black cursor-pointer"
                                                title="ล้างการกรองช่างคนนี้"
                                            >
                                                ✕
                                            </button>
                                        </span>
                                    </div>
                                )}
                            </form>
                        </div>

                        {/* Mobile Sort Bar */}
                        <div className="md:hidden flex items-center justify-between gap-2 px-4 py-2.5 border-b border-gray-100 bg-gray-50/60">
                            <div className="flex items-center gap-1.5 text-xs font-semibold text-gray-500">
                                <svg className="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                                </svg>
                                <span>เรียงลำดับ:</span>
                            </div>
                            <div className="flex items-center gap-1.5">
                                <select
                                    value={currentSort}
                                    onChange={(e) => handleSortDirect(e.target.value, currentDirection)}
                                    aria-label="เลือกฟิลด์สำหรับเรียงลำดับ"
                                    className="text-xs font-medium border-gray-200 rounded-lg py-1 px-2.5 bg-white text-gray-700 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs"
                                >
                                    <option value="created_at">วันที่แจ้งล่าสุด (Default)</option>
                                    <option value="ticket_no">รหัสใบงาน (Ticket ID)</option>
                                    <option value="priority">ระดับความเร่งด่วน (Priority)</option>
                                    <option value="status">สถานะงาน (Status)</option>
                                    <option value="title">หัวข้อปัญหา (Title)</option>
                                    <option value="escalated_to_team">ทีมที่รับผิดชอบ (Team)</option>
                                </select>
                                <button
                                    type="button"
                                    onClick={() => handleSortDirect(currentSort, currentDirection === 'asc' ? 'desc' : 'asc')}
                                    title={currentDirection === 'asc' ? 'เรียงจากน้อยไปมาก (เก่า-ใหม่, A-Z)' : 'เรียงจากมากไปน้อย (ใหม่-เก่า, Z-A)'}
                                    aria-label="สลับทิศทางการเรียงลำดับ"
                                    className="p-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-100 text-gray-600 focus:ring-1 focus:ring-indigo-500 transition-colors shadow-2xs"
                                >
                                    {currentDirection === 'asc' ? (
                                        <svg className="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4" />
                                        </svg>
                                    ) : (
                                        <svg className="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                                        </svg>
                                    )}
                                </button>
                            </div>
                        </div>

                        {/* Mobile Card View */}
                        <div className="md:hidden space-y-4 p-4">
                            {cases.data && cases.data.length > 0 ? (
                                cases.data.map((caseItem) => {
                                    const isHighlighted = highlightedTicketIds.has(caseItem.id);
                                    return (
                                        <a
                                            key={caseItem.id}
                                            href={route('tickets.show', caseItem.id)}
                                            onClick={() => markTicketAsViewed(caseItem.id, auth.user?.id)}
                                            className={`block rounded-xl shadow-sm p-4 transition-all relative overflow-hidden ${
                                                isHighlighted
                                                    ? 'bg-amber-50/90 border-2 border-amber-400 ring-2 ring-amber-300 shadow-md animate-pulse'
                                                    : 'bg-white border border-gray-200 hover:border-indigo-300 hover:shadow-md'
                                            }`}
                                        >
                                            <div className="flex justify-between items-start mb-2">
                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                    <span className="text-sm font-bold text-indigo-600">#{caseItem.ticket_no}</span>
                                                    {caseItem.pcar_no && (
                                                        <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                                            #{caseItem.pcar_no}
                                                        </span>
                                                    )}
                                                    {isHighlighted && (
                                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white shadow-xs">
                                                            <span className="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                                            เพิ่งเข้ามา
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="flex flex-col items-end gap-1">
                                                    {getStatusBadge(caseItem.status, caseItem.status_label)}
                                                    {renderSlaStatus(caseItem)}
                                                    {caseItem.task2_status_label && (
                                                        <span className={`px-2 py-0.5 inline-flex text-[10px] leading-4 font-bold rounded-full shadow-sm ${caseItem.task2_status_color}`}>
                                                            {caseItem.task2_status_label}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            <h4 className="text-base font-semibold text-gray-900 mb-1 leading-tight">{caseItem.title}</h4>
                                            <div className="text-xs text-gray-500 mb-3 flex flex-wrap items-center gap-1.5">
                                                <span>โดย: {caseItem.requester_name} ({caseItem.department || '-'})</span>
                                                {caseItem.actual_user_name && (
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                        <svg className="w-3 h-3 text-purple-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                        แจ้งแทน: {caseItem.actual_user_name}
                                                    </span>
                                                )}
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
                                    );
                                })
                            ) : (
                                <div className="text-center py-10">
                                    <svg className="h-10 w-10 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p className="text-sm font-medium text-gray-500">
                                        {managerTab === 'task2' ? 'ไม่พบเคสมาตรการป้องกันปัญหา (Task 2) ในขณะนี้' : 'ไม่พบใบงานแจ้งซ่อมในขณะนี้'}
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Desktop Table View */}
                        <div className="hidden md:block overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50/70">
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
                                            label="ข้อมูลผู้แจ้ง & ปัญหา"
                                            subLabel="Requester & Issue"
                                        />
                                        <SortableHeader
                                            field="priority"
                                            currentSort={currentSort}
                                            currentDirection={currentDirection}
                                            onSort={handleSort}
                                            label="ความเร่งด่วน"
                                            subLabel="Priority"
                                        />
                                        <SortableHeader
                                            field="escalated_to_team"
                                            currentSort={currentSort}
                                            currentDirection={currentDirection}
                                            onSort={handleSort}
                                            label="ทีมที่รับผิดชอบ"
                                            subLabel="Assigned Team"
                                        />
                                        <SortableHeader
                                            field="status"
                                            currentSort={currentSort}
                                            currentDirection={currentDirection}
                                            onSort={handleSort}
                                            label="สถานะ"
                                            subLabel="Status & SLA"
                                        />
                                        <th scope="col" className="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            จัดการ
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 bg-white">
                                    {cases.data && cases.data.length > 0 ? (
                                        cases.data.map((caseItem) => {
                                            const isHighlighted = highlightedTicketIds.has(caseItem.id);
                                            return (
                                                <tr
                                                    key={caseItem.id}
                                                    className={`transition-all duration-300 ${
                                                        isHighlighted
                                                            ? 'bg-amber-50/95 ring-2 ring-inset ring-amber-400 shadow-xs'
                                                            : 'hover:bg-slate-50'
                                                    }`}
                                                >
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center gap-1.5 flex-wrap">
                                                            <a
                                                                href={route('tickets.show', caseItem.id)}
                                                                onClick={() => markTicketAsViewed(caseItem.id, auth.user?.id)}
                                                                className="text-sm font-bold text-indigo-600 hover:text-indigo-900 hover:underline"
                                                            >
                                                                #{caseItem.ticket_no}
                                                            </a>
                                                            {caseItem.pcar_no && (
                                                                <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                                                    #{caseItem.pcar_no}
                                                                </span>
                                                            )}
                                                            {isHighlighted && (
                                                                <span className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white shadow-xs animate-bounce">
                                                                    <span className="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                                                    NEW
                                                                </span>
                                                            )}
                                                        </div>
                                                        <div className="text-xs text-gray-400 mt-0.5">
                                                            {formatRelativeTime(caseItem.created_at)}
                                                        </div>
                                                    </td>

                                                    <td className="px-6 py-4">
                                                        <div className="text-sm font-semibold text-gray-900">{caseItem.title}</div>
                                                        <div className="text-xs text-gray-500 mt-1 flex flex-wrap items-center gap-1.5">
                                                            <span>โดย: {caseItem.requester_name} ({caseItem.department || '-'})</span>
                                                            {caseItem.actual_user_name && (
                                                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                                    <svg className="w-3 h-3 text-purple-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                                    </svg>
                                                                    แจ้งแทน: {caseItem.actual_user_name}
                                                                </span>
                                                            )}
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
                                                        <div className="mt-1">
                                                            {renderSlaStatus(caseItem)}
                                                        </div>
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
                                                            onClick={() => markTicketAsViewed(caseItem.id, auth.user?.id)}
                                                            className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                                                        >
                                                            ดูรายละเอียด
                                                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                        </a>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-12 text-center text-gray-500">
                                                <div className="flex flex-col items-center">
                                                    <svg className="h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                    <p className="text-sm font-medium">
                                                        {managerTab === 'task2' ? 'ไม่พบเคสมาตรการป้องกันปัญหา (Task 2) ในขณะนี้' : 'ไม่พบใบงานแจ้งซ่อมในขณะนี้'}
                                                    </p>
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
