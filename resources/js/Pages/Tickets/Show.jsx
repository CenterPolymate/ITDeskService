import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

// Format Date to Thai standard
function formatThaiDateTime(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat('th-TH', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(d) + ' น.';
    } catch {
        return dateStr;
    }
}

function formatThaiDateOnly(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat('th-TH', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(d);
    } catch {
        return dateStr;
    }
}

export default function Show({ ticket }) {
    const user = usePage().props.auth.user;
    const flash = usePage().props.flash || {};

    // Determine permissions
    const isRequester = user.email === ticket.requester_email;
    const isHelpdesk = user.role === 'helpdesk';
    const isManager = typeof user.role === 'string' && user.role.includes('manager');

    let isAssignedTeam = false;
    if (ticket.escalated_to_team && user.role && user.role.startsWith('team_')) {
        const teamMap = { team_hardware: 'Hardware', team_network: 'Network', team_software: 'Software' };
        if (teamMap[user.role] === ticket.escalated_to_team) {
            isAssignedTeam = true;
        }
    }
    if (!ticket.escalated_to_team && user.role === 'helpdesk') {
        isAssignedTeam = true;
    }

    const canEditNotes = (isAssignedTeam || (user.role === 'helpdesk' && !ticket.escalated_to_team)) &&
        ['in_progress', 'resolved'].includes(ticket.status);

    const canTriage = (isHelpdesk || isManager) && (
        ['pending', 'assigned'].includes(ticket.status) ||
        (!ticket.escalated_to_team && !['resolved', 'approved', 'closed', 'cancelled'].includes(ticket.status))
    );

    const canCancel = (isRequester || isHelpdesk || isManager) &&
        ['pending', 'assigned', 'analyzing', 'in_progress'].includes(ticket.status);

    // Live auto-polling for comments and ticket status every 15s
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ only: ['ticket'], preserveScroll: true });
        }, 15000);
        return () => clearInterval(interval);
    }, []);

    // Scroll comments to bottom
    const commentsContainerRef = useRef(null);
    useEffect(() => {
        if (commentsContainerRef.current) {
            commentsContainerRef.current.scrollTop = commentsContainerRef.current.scrollHeight;
        }
    }, [ticket.comments]);

    // SLA Countdown
    const [timeLeft, setTimeLeft] = useState('--:--:--');
    const [isOverdue, setIsOverdue] = useState(false);

    useEffect(() => {
        if (!ticket.sla_due_at || ['closed', 'cancelled'].includes(ticket.status)) return;

        const updateSla = () => {
            const dueTime = new Date(ticket.sla_due_at).getTime();
            const now = new Date().getTime();
            const distance = dueTime - now;

            const overdue = distance < 0;
            setIsOverdue(overdue);

            const absDistance = Math.abs(distance);
            const days = Math.floor(absDistance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((absDistance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((absDistance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((absDistance % (1000 * 60)) / 1000);

            let timeString = '';
            if (days > 0) timeString += `${days}d `;
            timeString += `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            setTimeLeft((overdue ? '-' : '') + timeString);
        };

        updateSla();
        const interval = setInterval(updateSla, 1000);
        return () => clearInterval(interval);
    }, [ticket.sla_due_at, ticket.status]);

    // Acceptance 5-min timer for Helpdesk
    const [nowTime, setNowTime] = useState(Date.now());
    useEffect(() => {
        const interval = setInterval(() => setNowTime(Date.now()), 1000);
        return () => clearInterval(interval);
    }, []);

    const targetTimeMs = ticket.resolved_at ? new Date(ticket.resolved_at).getTime() + 5 * 60 * 1000 : 0;
    const timeLeftMs = Math.max(0, targetTimeMs - nowTime);
    const canAccept = isRequester || timeLeftMs === 0;
    const acceptTimerSeconds = Math.floor(timeLeftMs / 1000);
    const acceptTimerFormatted = `${String(Math.floor(acceptTimerSeconds / 60)).padStart(2, '0')}:${String(acceptTimerSeconds % 60).padStart(2, '0')}`;

    // Modals & Edit Mode states
    const [editNotesMode, setEditNotesMode] = useState(false);
    const [showCsatModal, setShowCsatModal] = useState(false);
    const [csatRating, setCsatRating] = useState(5);
    const [csatHover, setCsatHover] = useState(0);
    const [csatFeedback, setCsatFeedback] = useState('');
    const [showCancelModal, setShowCancelModal] = useState(false);
    const [cancelReason, setCancelReason] = useState('');
    const [previewImage, setPreviewImage] = useState(null);

    // Triage Form
    const { data: triageData, setData: setTriageData, put: putTriage, processing: triageProcessing } = useForm({
        priority: ticket.status !== 'pending' ? (ticket.priority || '') : '',
        escalated_to_team: ticket.status !== 'pending' ? (ticket.escalated_to_team || 'None') : '',
    });

    const submitTriage = (e) => {
        e.preventDefault();
        putTriage(route('tickets.assign', ticket.id), {
            preserveScroll: true,
        });
    };

    // Inline Edit Notes Form
    const { data: notesData, setData: setNotesData, put: putNotes, processing: notesProcessing } = useForm({
        action: 'update_notes',
        why_1: ticket.why_1 || '',
        why_2: ticket.why_2 || '',
        why_3: ticket.why_3 || '',
        root_cause_detail: ticket.root_cause_detail || '',
        resolution_notes: ticket.resolution_notes || '',
    });

    const submitNotes = (e) => {
        e.preventDefault();
        putNotes(route('tickets.updateStatus', ticket.id), {
            preserveScroll: true,
            onSuccess: () => setEditNotesMode(false),
        });
    };

    // Action Forms for Tier 2
    // Analyzing form
    const { data: analysisData, setData: setAnalysisData, put: putAnalysis, processing: analysisProcessing } = useForm({
        action: 'update_analysis',
        why_1: ticket.why_1 || '',
        why_2: ticket.why_2 || '',
        why_3: ticket.why_3 || '',
        root_cause_detail: ticket.root_cause_detail || '',
    });

    // In Progress Form
    const { data: resolveData, setData: setResolveData, put: putResolve, processing: resolveProcessing } = useForm({
        action: 'resolve',
        resolution_notes: '',
    });

    // Manager Review Form
    const { data: managerData, setData: setManagerData, put: putManager, processing: managerProcessing } = useForm({
        action: 'close',
        requires_preventive_measure: ticket.requires_preventive_measure === true ? '1' : (ticket.requires_preventive_measure === false ? '0' : ''),
        escalated_to_team: ticket.escalated_to_team || '',
    });

    // Task 2: Preventive Action Form
    const initialSpecific = Array.isArray(ticket.preventive_measure_specific) && ticket.preventive_measure_specific.length > 0
        ? ticket.preventive_measure_specific
        : [{ detail: '', due_date: '' }];

    const initialSystemic = Array.isArray(ticket.preventive_measure_systemic) && ticket.preventive_measure_systemic.length > 0
        ? ticket.preventive_measure_systemic
        : [{ detail: '', due_date: '' }];

    const { data: task2Data, setData: setTask2Data, put: putTask2, processing: task2Processing } = useForm({
        action: 'preventive_action',
        why_1: ticket.why_1 || '',
        why_2: ticket.why_2 || '',
        why_3: ticket.why_3 || '',
        root_cause_detail: ticket.root_cause_detail || '',
        resolution_notes: ticket.resolution_notes || '',
        preventive_measure_specific: initialSpecific,
        preventive_measure_systemic: initialSystemic,
        root_cause_category: ticket.root_cause_category || '',
    });

    // New Comment Form
    const { data: commentData, setData: setCommentData, post: postComment, processing: commentProcessing, reset: resetComment } = useForm({
        message: '',
        attachment: null,
    });

    const submitComment = (e) => {
        e.preventDefault();
        postComment(route('tickets.comments.store', ticket.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                resetComment();
                const fileInput = document.getElementById('comment-attachment');
                if (fileInput) fileInput.value = '';
            },
        });
    };

    // Calculate total duration string for Task 1
    let totalDurationTask1 = '';
    if (['closed', 'cancelled'].includes(ticket.status)) {
        const endStr = ticket.status === 'closed' ? (ticket.closed_at || ticket.updated_at) : (ticket.cancelled_at || ticket.updated_at);
        if (ticket.created_at && endStr) {
            const diffMs = Math.max(0, new Date(endStr).getTime() - new Date(ticket.created_at).getTime());
            const mins = Math.floor(diffMs / (1000 * 60));
            const d = Math.floor(mins / (60 * 24));
            const h = Math.floor((mins % (60 * 24)) / 60);
            const m = mins % 60;
            if (d > 0) totalDurationTask1 += `${d} วัน `;
            if (h > 0) totalDurationTask1 += `${h} ชั่วโมง `;
            if (m > 0) totalDurationTask1 += `${m} นาที`;
            if (!totalDurationTask1) totalDurationTask1 = 'น้อยกว่า 1 นาที';
        }
    }

    return (
        <>
            <Head title={`ใบงาน #${ticket.ticket_no} - ITDeskService`} />

            <div className="bg-white border-b border-gray-200 shadow-sm">
                <div className="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <div className="flex items-center gap-3">
                                <h2 className="font-bold text-xl sm:text-2xl text-slate-800 leading-tight">
                                    ใบแจ้งซ่อม #{ticket.ticket_no}
                                </h2>
                                {ticket.pcar_no && (
                                    <span className="px-2.5 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-800 border border-teal-200">
                                        PCAR #{ticket.pcar_no}
                                    </span>
                                )}
                            </div>
                            <p className="text-xs sm:text-sm text-slate-500 mt-1">
                                {ticket.company} • {ticket.department || 'ไม่ระบุแผนก'}
                            </p>
                        </div>

                        <div className="flex items-center gap-3">
                            {user.role !== 'user' && (
                                <a
                                    href={route('tickets.print', ticket.id)}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 px-3.5 py-2 border border-slate-300 shadow-sm text-sm font-bold rounded-xl text-slate-700 bg-white hover:bg-slate-50 hover:border-slate-400 transition"
                                >
                                    <svg className="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    พิมพ์ใบงาน (Print)
                                </a>
                            )}
                            <Link
                                href={route('dashboard')}
                                className="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                            >
                                &larr; แดชบอร์ด
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div className="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {/* Flash Messages */}
                {flash.success && (
                    <div className="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-xl text-emerald-800 text-sm font-medium shadow-sm">
                        {flash.success}
                    </div>
                )}
                {flash.error && (
                    <div className="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-xl text-rose-800 text-sm font-medium shadow-sm">
                        {flash.error}
                    </div>
                )}

                {/* Cancelled Banner */}
                {ticket.status === 'cancelled' && (
                    <div className="bg-slate-100 border-l-4 border-slate-500 p-5 rounded-2xl shadow-sm mb-6">
                        <div className="flex items-start gap-3">
                            <div className="p-2 bg-slate-200 rounded-xl text-slate-700">
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="text-base font-bold text-slate-800">ใบงานนี้ถูกยกเลิกแล้ว</h3>
                                <p className="mt-1 text-sm text-slate-700">
                                    <strong>เหตุผล:</strong> {ticket.cancellation_reason || 'ไม่ได้ระบุเหตุผล'}
                                </p>
                                <p className="mt-1 text-xs text-slate-500">
                                    ยกเลิกเมื่อ {formatThaiDateTime(ticket.cancelled_at)} โดย {ticket.cancelled_by?.name || 'Unknown'}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* SLA Visualizer */}
                {ticket.sla_due_at && !['closed', 'cancelled'].includes(ticket.status) && (
                    <div className={`p-5 rounded-2xl shadow-sm mb-6 flex justify-between items-center border-l-4 transition-colors duration-500 ${
                        isOverdue ? 'bg-rose-50 border-rose-500' : 'bg-indigo-50 border-indigo-500'
                    }`}>
                        <div>
                            <h3 className={`text-sm font-bold ${isOverdue ? 'text-rose-800' : 'text-indigo-800'}`}>
                                กำหนดส่งมอบงาน (SLA Due Date)
                            </h3>
                            <p className={`text-xs mt-1 ${isOverdue ? 'text-rose-600' : 'text-indigo-600'}`}>
                                ภายใน: {formatThaiDateTime(ticket.sla_due_at)}
                            </p>
                        </div>
                        <div className="text-right">
                            <span className={`text-2xl font-black font-mono tracking-wider ${isOverdue ? 'text-rose-700' : 'text-indigo-700'}`}>
                                {timeLeft}
                            </span>
                            <div className={`text-xs font-bold uppercase mt-0.5 ${isOverdue ? 'text-rose-600 animate-pulse' : 'text-indigo-600'}`}>
                                {isOverdue ? 'Overdue (เกินกำหนด SLA)' : 'Remaining (เวลาที่เหลือ)'}
                            </div>
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* LEFT COLUMN: Main Details & Workflow Actions */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* 1. Problem Description Card */}
                        <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200">
                            <div className="mb-6 border-b border-slate-100 pb-4">
                                <div className="flex flex-wrap items-center justify-between gap-2 mb-1">
                                    <span className="px-3 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-bold">
                                        หมวดหมู่: {ticket.category || 'อื่นๆ'}
                                    </span>
                                    <span className="text-xs text-slate-500 font-medium">
                                        แจ้งเมื่อ: {formatThaiDateTime(ticket.created_at)}
                                    </span>
                                </div>
                                <h3 className="text-xl sm:text-2xl font-black text-slate-900 mt-2">
                                    {ticket.title}
                                </h3>
                            </div>

                            <div className="mb-6">
                                <h4 className="text-sm font-bold text-slate-700 mb-2">รายละเอียดอาการ:</h4>
                                <div className="bg-slate-50 p-4 sm:p-5 rounded-2xl border border-slate-100 text-slate-700 whitespace-pre-wrap leading-relaxed text-sm sm:text-base">
                                    {ticket.description}
                                </div>
                            </div>

                            {/* Machine Info */}
                            {(ticket.machine_name || ticket.machine_code || ticket.is_machine_stopped !== null) && (
                                <div className="mb-6 p-4 bg-purple-50/50 rounded-2xl border border-purple-100">
                                    <h4 className="text-xs font-bold text-purple-700 uppercase tracking-wider mb-2">ข้อมูลเครื่องจักร / อุปกรณ์</h4>
                                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs sm:text-sm">
                                        <div>
                                            <span className="text-slate-500 block">ชื่ออุปกรณ์:</span>
                                            <span className="font-bold text-slate-800">{ticket.machine_name || '-'}</span>
                                        </div>
                                        <div>
                                            <span className="text-slate-500 block">รหัสอุปกรณ์:</span>
                                            <span className="font-bold text-slate-800">{ticket.machine_code || '-'}</span>
                                        </div>
                                        <div>
                                            <span className="text-slate-500 block">สถานะเครื่องจักร:</span>
                                            <span className={`font-bold ${ticket.is_machine_stopped ? 'text-rose-600' : 'text-emerald-600'}`}>
                                                {ticket.is_machine_stopped ? 'เครื่องจักรหยุดทำงาน' : 'ใช้งานได้ปกติ'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Attachments */}
                            {ticket.attachments && ticket.attachments.length > 0 && (
                                <div className="mt-6 border-t border-slate-100 pt-6">
                                    <h4 className="text-sm font-bold text-slate-700 mb-3">
                                        รูปภาพหลักฐาน ({ticket.attachments.length} รูป):
                                    </h4>
                                    <div className="flex flex-wrap gap-4">
                                        {ticket.attachments.map((att, idx) => (
                                            <div
                                                key={idx}
                                                onClick={() => setPreviewImage(`/storage/${att.file_path}`)}
                                                className="cursor-pointer group relative border-2 border-slate-100 rounded-2xl overflow-hidden bg-slate-50 p-1.5 shadow-sm hover:shadow-md transition"
                                            >
                                                <img
                                                    src={`/storage/${att.file_path}`}
                                                    alt="Evidence"
                                                    className="w-32 h-32 sm:w-40 sm:h-40 object-cover rounded-xl group-hover:scale-105 transition-transform duration-300"
                                                />
                                                <div className="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold rounded-xl">
                                                    คลิกเพื่อขยาย
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Root Cause & Resolution Notes Section */}
                            <div>
                                {!editNotesMode ? (
                                    <>
                                        {/* RCA Display */}
                                        {ticket.root_cause_detail && user.role !== 'user' && (
                                            <div className="mt-6 border-t border-slate-100 pt-6">
                                                <div className="flex justify-between items-center mb-3">
                                                    <h4 className="text-sm font-bold text-purple-700 flex items-center gap-2">
                                                        <span>🔍</span> การวิเคราะห์หาสาเหตุ (Root Cause Analysis):
                                                    </h4>
                                                    {canEditNotes && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setEditNotesMode(true)}
                                                            className="text-xs font-bold text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-xl border border-amber-200 transition"
                                                        >
                                                            แก้ไขบันทึกการซ่อม
                                                        </button>
                                                    )}
                                                </div>
                                                <div className="bg-purple-50/70 p-4 sm:p-5 rounded-2xl border border-purple-200 text-sm space-y-2.5">
                                                    {ticket.why_1 && (
                                                        <div className="flex gap-2">
                                                            <span className="font-bold text-purple-900 w-16 shrink-0">ทำไม 1:</span>
                                                            <span className="text-slate-800">{ticket.why_1}</span>
                                                        </div>
                                                    )}
                                                    {ticket.why_2 && (
                                                        <div className="flex gap-2">
                                                            <span className="font-bold text-purple-900 w-16 shrink-0">ทำไม 2:</span>
                                                            <span className="text-slate-800">{ticket.why_2}</span>
                                                        </div>
                                                    )}
                                                    {ticket.why_3 && (
                                                        <div className="flex gap-2">
                                                            <span className="font-bold text-purple-900 w-16 shrink-0">ทำไม 3:</span>
                                                            <span className="text-slate-800">{ticket.why_3}</span>
                                                        </div>
                                                    )}
                                                    <div className="flex gap-2 pt-2 border-t border-purple-200">
                                                        <span className="font-bold text-purple-900 w-28 shrink-0">สาเหตุรากเหง้า:</span>
                                                        <span className="font-semibold text-slate-900">{ticket.root_cause_detail}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* Resolution Notes Display */}
                                        {ticket.resolution_notes && (
                                            <div className="mt-6 border-t border-slate-100 pt-6">
                                                <div className="flex justify-between items-center mb-2">
                                                    <h4 className="text-sm font-bold text-emerald-700 flex items-center gap-2">
                                                        <span>✅</span> บันทึกการแก้ไขปัญหาเฉพาะหน้า (Resolution Notes):
                                                    </h4>
                                                    {canEditNotes && !ticket.root_cause_detail && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setEditNotesMode(true)}
                                                            className="text-xs font-bold text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-xl border border-amber-200 transition"
                                                        >
                                                            แก้ไขบันทึกการซ่อม
                                                        </button>
                                                    )}
                                                </div>
                                                <div className="bg-emerald-50/70 p-4 sm:p-5 rounded-2xl border border-emerald-200 text-slate-800 text-sm whitespace-pre-wrap leading-relaxed">
                                                    {ticket.resolution_notes}
                                                </div>
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    /* Inline Edit Notes Form */
                                    <div className="mt-6 border-t border-amber-200 pt-6">
                                        <div className="p-5 sm:p-6 bg-amber-50/60 rounded-3xl border border-amber-200">
                                            <h4 className="font-bold text-amber-900 text-base mb-4 pb-2 border-b border-amber-200">
                                                แก้ไขบันทึกการซ่อม (Inline Edit)
                                            </h4>
                                            <form onSubmit={submitNotes} className="space-y-4">
                                                {ticket.root_cause_detail && (
                                                    <div className="space-y-3">
                                                        <div className="flex items-center gap-3">
                                                            <label className="w-20 text-xs font-bold text-slate-700 shrink-0">ทำไม 1 :</label>
                                                            <input
                                                                type="text"
                                                                value={notesData.why_1}
                                                                onChange={(e) => setNotesData('why_1', e.target.value)}
                                                                className="flex-1 rounded-xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                                            />
                                                        </div>
                                                        <div className="flex items-center gap-3">
                                                            <label className="w-20 text-xs font-bold text-slate-700 shrink-0">ทำไม 2 :</label>
                                                            <input
                                                                type="text"
                                                                value={notesData.why_2}
                                                                onChange={(e) => setNotesData('why_2', e.target.value)}
                                                                className="flex-1 rounded-xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                                            />
                                                        </div>
                                                        <div className="flex items-center gap-3">
                                                            <label className="w-20 text-xs font-bold text-slate-700 shrink-0">ทำไม 3 :</label>
                                                            <input
                                                                type="text"
                                                                value={notesData.why_3}
                                                                onChange={(e) => setNotesData('why_3', e.target.value)}
                                                                className="flex-1 rounded-xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                                            />
                                                        </div>
                                                        <div className="pt-2 border-t border-amber-200">
                                                            <label className="block text-xs font-bold text-slate-700 mb-1">สาเหตุรากเหง้า :</label>
                                                            <textarea
                                                                rows={3}
                                                                required
                                                                value={notesData.root_cause_detail}
                                                                onChange={(e) => setNotesData('root_cause_detail', e.target.value)}
                                                                className="w-full rounded-xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                                            />
                                                        </div>
                                                    </div>
                                                )}

                                                <div>
                                                    <label className="block text-xs font-bold text-slate-700 mb-1">
                                                        บันทึกการแก้ไขปัญหาเฉพาะหน้า :
                                                    </label>
                                                    <textarea
                                                        rows={3}
                                                        value={notesData.resolution_notes}
                                                        onChange={(e) => setNotesData('resolution_notes', e.target.value)}
                                                        className="w-full rounded-xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                                    />
                                                </div>

                                                <div className="flex justify-end gap-3 pt-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => setEditNotesMode(false)}
                                                        className="px-5 py-2 bg-white text-slate-700 font-bold rounded-xl border border-slate-300 hover:bg-slate-50 text-sm transition"
                                                    >
                                                        ยกเลิก
                                                    </button>
                                                    <button
                                                        type="submit"
                                                        disabled={notesProcessing}
                                                        className="px-6 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-sm shadow transition disabled:opacity-50"
                                                    >
                                                        💾 บันทึกการแก้ไข
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* 2. Action Form for Assigned Team (Tier 2 Workflow) */}
                        {isAssignedTeam && ['assigned', 'analyzing', 'in_progress'].includes(ticket.status) && (
                            <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border-t-4 border-t-emerald-500 border-x border-b border-slate-200">
                                <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                                    <span className="p-2 bg-emerald-50 text-emerald-600 rounded-xl">⚙️</span>
                                    การดำเนินการของช่าง (Action)
                                </h3>

                                {/* Step: assigned -> start_analyzing */}
                                {ticket.status === 'assigned' && (
                                    <div className="text-center py-6">
                                        <p className="text-slate-600 mb-5 font-medium">
                                            คุณได้รับมอบหมายงานนี้ กรุณากดปุ่มด้านล่างเพื่อเริ่มต้นกระบวนการสืบสภาพและวิเคราะห์หาสาเหตุ
                                        </p>
                                        <button
                                            type="button"
                                            onClick={() => router.put(route('tickets.updateStatus', ticket.id), { action: 'start_analyzing' })}
                                            className="px-8 py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-2xl shadow-md transition-all duration-200"
                                        >
                                            🔍 เริ่มดำเนินการสืบสภาพ
                                        </button>
                                    </div>
                                )}

                                {/* Step: analyzing -> update_analysis */}
                                {ticket.status === 'analyzing' && (
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                            putAnalysis(route('tickets.updateStatus', ticket.id), { preserveScroll: true });
                                        }}
                                        className="space-y-4"
                                    >
                                        <div className="p-4 bg-purple-50/50 rounded-2xl border border-purple-100 space-y-3">
                                            <h4 className="font-bold text-purple-900 text-sm border-b border-purple-200 pb-2">
                                                การวิเคราะห์หาสาเหตุ (Why-Why Analysis)
                                            </h4>
                                            <div className="flex items-center gap-3">
                                                <label className="w-16 font-bold text-slate-700 text-xs shrink-0">ทำไม 1 :</label>
                                                <input
                                                    type="text"
                                                    value={analysisData.why_1}
                                                    onChange={(e) => setAnalysisData('why_1', e.target.value)}
                                                    placeholder="เกิดอะไรขึ้น..."
                                                    className="flex-1 rounded-xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500"
                                                />
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <label className="w-16 font-bold text-slate-700 text-xs shrink-0">ทำไม 2 :</label>
                                                <input
                                                    type="text"
                                                    value={analysisData.why_2}
                                                    onChange={(e) => setAnalysisData('why_2', e.target.value)}
                                                    placeholder="ทำไมจึงเกิดสิ่งนั้น..."
                                                    className="flex-1 rounded-xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500"
                                                />
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <label className="w-16 font-bold text-slate-700 text-xs shrink-0">ทำไม 3 :</label>
                                                <input
                                                    type="text"
                                                    value={analysisData.why_3}
                                                    onChange={(e) => setAnalysisData('why_3', e.target.value)}
                                                    placeholder="ทำไมจึงเป็นเช่นนั้น..."
                                                    className="flex-1 rounded-xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500"
                                                />
                                            </div>
                                            <div className="pt-2 border-t border-purple-200">
                                                <label className="block text-xs font-bold text-slate-800 mb-1">
                                                    สาเหตุรากเหง้า (Root Cause) <span className="text-rose-500">*</span>
                                                </label>
                                                <textarea
                                                    rows={3}
                                                    required
                                                    value={analysisData.root_cause_detail}
                                                    onChange={(e) => setAnalysisData('root_cause_detail', e.target.value)}
                                                    placeholder="ระบุสาเหตุที่แท้จริงของปัญหา เพื่อเริ่มขั้นตอนการซ่อม..."
                                                    className="w-full rounded-xl border-slate-300 text-sm focus:border-purple-500 focus:ring-purple-500"
                                                />
                                            </div>
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={analysisProcessing}
                                            className="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow transition disabled:opacity-50"
                                        >
                                            💾 บันทึกสาเหตุและเริ่มการแก้ไข
                                        </button>
                                        <p className="text-xs text-slate-500 text-center">
                                            * เมื่อระบุสาเหตุของปัญหา ระบบจะจบบันทึกเวลาสืบสภาพและเริ่มขั้นตอนแก้ไขทันที
                                        </p>
                                    </form>
                                )}

                                {/* Step: in_progress -> resolve */}
                                {ticket.status === 'in_progress' && (
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                            putResolve(route('tickets.updateStatus', ticket.id), { preserveScroll: true });
                                        }}
                                        className="space-y-4"
                                    >
                                        <div>
                                            <label className="block font-bold text-slate-700 text-sm mb-2">
                                                บันทึกการแก้ไขปัญหาเฉพาะหน้า (Resolution Notes) <span className="text-rose-500">*</span>
                                            </label>
                                            <textarea
                                                rows={4}
                                                required
                                                value={resolveData.resolution_notes}
                                                onChange={(e) => setResolveData('resolution_notes', e.target.value)}
                                                placeholder="ระบุวิธีแก้ไข เช่น เปลี่ยนอะไหล่ หรือตั้งค่าระบบ..."
                                                className="w-full rounded-2xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 leading-relaxed"
                                            />
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={resolveProcessing}
                                            className="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl shadow-md transition disabled:opacity-50"
                                        >
                                            ✅ บันทึกและส่งมอบงานให้ผู้แจ้งตรวจสอบ
                                        </button>
                                    </form>
                                )}
                            </div>
                        )}

                        {/* 3. Action Form for Acceptance (Requester / Helpdesk) */}
                        {ticket.status === 'resolved' && (isRequester || isHelpdesk) && (
                            <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border-t-4 border-t-amber-500 border-x border-b border-slate-200">
                                <h3 className="text-lg font-bold text-slate-800 mb-2 flex items-center gap-2">
                                    <span className="p-2 bg-amber-50 text-amber-600 rounded-xl">⭐</span>
                                    รับทราบการแก้ไข (Accept Resolution)
                                </h3>
                                <p className="text-sm text-slate-600 mb-4">
                                    การแก้ไขเสร็จสิ้นแล้ว กรุณากดยืนยันการรับทราบการแก้ไขเพื่อปิดใบงาน
                                </p>

                                <button
                                    type="button"
                                    disabled={!canAccept}
                                    onClick={() => setShowCsatModal(true)}
                                    className={`w-full py-3.5 px-4 font-bold rounded-2xl shadow transition text-center ${
                                        canAccept
                                            ? 'bg-amber-500 hover:bg-amber-600 text-white'
                                            : 'bg-slate-200 text-slate-400 cursor-not-allowed'
                                    }`}
                                >
                                    ✅ ยืนยันรับทราบและปิดใบงาน
                                    {isHelpdesk && !isRequester && !canAccept && (
                                        <span className="text-xs font-normal ml-2">
                                            (Helpdesk กดแทนได้ในอีก {acceptTimerFormatted} นาที)
                                        </span>
                                    )}
                                </button>
                            </div>
                        )}

                        {/* 4. Action Form for Manager Review */}
                        {ticket.status === 'closed' && user.role === 'manager' && (
                            <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border-t-4 border-t-slate-800 border-x border-b border-slate-200">
                                <h3 className="text-lg font-bold text-slate-800 mb-2 flex items-center gap-2">
                                    <span className="p-2 bg-slate-100 text-slate-800 rounded-xl">🛡️</span>
                                    การตรวจสอบจากหัวหน้า (Manager Review)
                                </h3>
                                <p className="text-sm text-slate-600 mb-4">
                                    สถานะเคสนี้คือปิดใบงานแล้ว หัวหน้าจำเป็นต้องตรวจสอบเพื่อระบุว่า "ต้องการมาตรการป้องกันปัญหา (Task 2)" หรือไม่
                                </p>

                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        putManager(route('tickets.updateStatus', ticket.id), { preserveScroll: true });
                                    }}
                                    className="space-y-4"
                                >
                                    <div>
                                        <label className="block font-bold text-slate-700 text-sm mb-2">
                                            มาตรการป้องกัน (Preventive Measure) <span className="text-rose-500">*</span>
                                        </label>
                                        <div className="flex items-center gap-6">
                                            <label className="inline-flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="requires_preventive_measure"
                                                    value="1"
                                                    checked={managerData.requires_preventive_measure === '1'}
                                                    onChange={(e) => setManagerData('requires_preventive_measure', e.target.value)}
                                                    className="text-slate-800 focus:ring-slate-800"
                                                />
                                                <span className="text-sm font-semibold text-slate-800">ต้องการมาตรการป้องกัน</span>
                                            </label>
                                            <label className="inline-flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="requires_preventive_measure"
                                                    value="0"
                                                    checked={managerData.requires_preventive_measure === '0'}
                                                    onChange={(e) => setManagerData('requires_preventive_measure', e.target.value)}
                                                    className="text-slate-800 focus:ring-slate-800"
                                                />
                                                <span className="text-sm font-semibold text-slate-800">ไม่ต้องการมาตรการป้องกัน</span>
                                            </label>
                                        </div>
                                    </div>

                                    {managerData.requires_preventive_measure === '1' && (
                                        <div className="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                                            <label className="block font-bold text-slate-700 text-sm">
                                                ส่งต่อให้ทีมเฉพาะทางดำเนินการ Task 2 <span className="text-rose-500">*</span>
                                            </label>
                                            <select
                                                required
                                                value={managerData.escalated_to_team}
                                                onChange={(e) => setManagerData('escalated_to_team', e.target.value)}
                                                className="w-full rounded-xl border-slate-300 text-sm focus:border-slate-800 focus:ring-slate-800"
                                            >
                                                <option value="" disabled>-- เลือกทีมที่จะมอบหมาย --</option>
                                                <option value="Hardware">ช่างฮาร์ดแวร์ (Hardware Team)</option>
                                                <option value="Network">ช่างเครือข่าย (Network Team)</option>
                                                <option value="Software">ช่างซอฟต์แวร์ (Software Team)</option>
                                            </select>
                                        </div>
                                    )}

                                    <button
                                        type="submit"
                                        disabled={managerProcessing}
                                        className="w-full py-3 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-2xl shadow transition disabled:opacity-50"
                                    >
                                        💾 บันทึกผลการตรวจสอบของหัวหน้า
                                    </button>
                                </form>
                            </div>
                        )}

                        {/* 5. Task 2: Preventive Action Form (For IT Staff) */}
                        {ticket.requires_preventive_measure === true && user.role !== 'user' && (
                            <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border-t-4 border-t-teal-500 border-x border-b border-slate-200">
                                <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                                    <span className="p-2 bg-teal-50 text-teal-600 rounded-xl">🛡️</span>
                                    Task 2: การป้องกันปัญหาไม่ให้เกิดซ้ำ (Preventive Action)
                                </h3>

                                {ticket.preventive_measure === 'assigned' ? (
                                    <div className="text-center py-6 bg-teal-50/50 rounded-2xl border border-teal-100 p-6">
                                        <p className="text-teal-900 font-medium mb-4">
                                            ผู้จัดการได้มอบหมายงาน Task 2 ให้กับทีมของคุณ กรุณากดปุ่มด้านล่างเพื่อเริ่มดำเนินการป้องกัน
                                        </p>
                                        <button
                                            type="button"
                                            onClick={() => router.put(route('tickets.updateStatus', ticket.id), { action: 'start_preventive_measure' })}
                                            className="px-8 py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-2xl shadow transition"
                                        >
                                            ▶️ เริ่มดำเนินการป้องกัน (Start Task 2)
                                        </button>
                                    </div>
                                ) : (
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                            putTask2(route('tickets.updateStatus', ticket.id), { preserveScroll: true });
                                        }}
                                        className="space-y-6"
                                    >
                                        {/* 3.1 Specific Measures */}
                                        <div className="p-4 sm:p-5 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                                            <div className="flex justify-between items-center">
                                                <label className="font-bold text-slate-800 text-sm">
                                                    3.1 มาตรการป้องกันเฉพาะกรณี (Specific Preventive Measure)
                                                </label>
                                                {ticket.preventive_measure !== 'done' && !isManager && (
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setTask2Data('preventive_measure_specific', [
                                                                ...task2Data.preventive_measure_specific,
                                                                { detail: '', due_date: '' },
                                                            ]);
                                                        }}
                                                        className="text-xs bg-teal-50 text-teal-700 border border-teal-200 hover:bg-teal-100 rounded-lg px-2.5 py-1 font-bold transition"
                                                    >
                                                        + เพิ่มข้อ
                                                    </button>
                                                )}
                                            </div>

                                            {task2Data.preventive_measure_specific.map((m, idx) => (
                                                <div key={idx} className="p-3 bg-white rounded-xl border border-slate-200 space-y-2">
                                                    <textarea
                                                        rows={2}
                                                        disabled={ticket.preventive_measure === 'done' || isManager}
                                                        value={m.detail}
                                                        onChange={(e) => {
                                                            const updated = [...task2Data.preventive_measure_specific];
                                                            updated[idx].detail = e.target.value;
                                                            setTask2Data('preventive_measure_specific', updated);
                                                        }}
                                                        placeholder="ระบุวิธีป้องกันสำหรับเคสนี้..."
                                                        className="w-full rounded-xl border-slate-200 text-sm focus:border-teal-500 focus:ring-teal-500"
                                                    />
                                                    <div className="flex justify-between items-center text-xs">
                                                        <div className="flex items-center gap-2">
                                                            <span className="text-slate-600 font-medium">วันกำหนดเสร็จ:</span>
                                                            <input
                                                                type="date"
                                                                disabled={ticket.preventive_measure === 'done' || isManager}
                                                                value={m.due_date || ''}
                                                                onChange={(e) => {
                                                                    const updated = [...task2Data.preventive_measure_specific];
                                                                    updated[idx].due_date = e.target.value;
                                                                    setTask2Data('preventive_measure_specific', updated);
                                                                }}
                                                                className="rounded-lg border-slate-200 text-xs py-1 px-2"
                                                            />
                                                        </div>
                                                        {task2Data.preventive_measure_specific.length > 1 && ticket.preventive_measure !== 'done' && !isManager && (
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    const updated = task2Data.preventive_measure_specific.filter((_, i) => i !== idx);
                                                                    setTask2Data('preventive_measure_specific', updated);
                                                                }}
                                                                className="text-rose-500 hover:underline font-bold"
                                                            >
                                                                ลบออก
                                                            </button>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>

                                        {/* 3.2 Systemic Measures */}
                                        <div className="p-4 sm:p-5 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                                            <div className="flex justify-between items-center">
                                                <label className="font-bold text-slate-800 text-sm">
                                                    3.2 มาตรการป้องกันทั้งระบบ (Systemic Preventive Measure)
                                                </label>
                                                {ticket.preventive_measure !== 'done' && !isManager && (
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setTask2Data('preventive_measure_systemic', [
                                                                ...task2Data.preventive_measure_systemic,
                                                                { detail: '', due_date: '' },
                                                            ]);
                                                        }}
                                                        className="text-xs bg-teal-50 text-teal-700 border border-teal-200 hover:bg-teal-100 rounded-lg px-2.5 py-1 font-bold transition"
                                                    >
                                                        + เพิ่มข้อ
                                                    </button>
                                                )}
                                            </div>

                                            {task2Data.preventive_measure_systemic.map((m, idx) => (
                                                <div key={idx} className="p-3 bg-white rounded-xl border border-slate-200 space-y-2">
                                                    <textarea
                                                        rows={2}
                                                        disabled={ticket.preventive_measure === 'done' || isManager}
                                                        value={m.detail}
                                                        onChange={(e) => {
                                                            const updated = [...task2Data.preventive_measure_systemic];
                                                            updated[idx].detail = e.target.value;
                                                            setTask2Data('preventive_measure_systemic', updated);
                                                        }}
                                                        placeholder="ระบุวิธีป้องกันไม่ให้ปัญหาเกิดซ้ำในระบบ..."
                                                        className="w-full rounded-xl border-slate-200 text-sm focus:border-teal-500 focus:ring-teal-500"
                                                    />
                                                    <div className="flex justify-between items-center text-xs">
                                                        <div className="flex items-center gap-2">
                                                            <span className="text-slate-600 font-medium">วันกำหนดเสร็จ:</span>
                                                            <input
                                                                type="date"
                                                                disabled={ticket.preventive_measure === 'done' || isManager}
                                                                value={m.due_date || ''}
                                                                onChange={(e) => {
                                                                    const updated = [...task2Data.preventive_measure_systemic];
                                                                    updated[idx].due_date = e.target.value;
                                                                    setTask2Data('preventive_measure_systemic', updated);
                                                                }}
                                                                className="rounded-lg border-slate-200 text-xs py-1 px-2"
                                                            />
                                                        </div>
                                                        {task2Data.preventive_measure_systemic.length > 1 && ticket.preventive_measure !== 'done' && !isManager && (
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    const updated = task2Data.preventive_measure_systemic.filter((_, i) => i !== idx);
                                                                    setTask2Data('preventive_measure_systemic', updated);
                                                                }}
                                                                className="text-rose-500 hover:underline font-bold"
                                                            >
                                                                ลบออก
                                                            </button>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>

                                        {/* 3.3 PCAR Category */}
                                        <div className="p-4 sm:p-5 bg-slate-50 rounded-2xl border border-slate-200">
                                            <label className="block font-bold text-slate-800 text-sm mb-2">
                                                3.3 สรุปปัญหา/สาเหตุเกิดจาก (P-CAR Category)
                                            </label>
                                            <div className="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
                                                {[
                                                    { cat: 'คน', subs: ['คน - ไม่รู้มาตรฐาน', 'คน - ไม่ทำตามมาตรฐาน', 'คน - ทำตามแล้วยังเกิด'] },
                                                    { cat: 'เครื่องจักร', subs: ['เครื่องจักร - ออกแบบไม่ดี', 'เครื่องจักร - ไม่ได้กำหนดมาตรฐาน'] },
                                                    { cat: 'วัสดุ', subs: ['วัสดุ - ไม่ได้กำหนดมาตรฐาน', 'วัสดุ - กำหนดไม่เหมาะสม'] },
                                                    { cat: 'วิธีการ', subs: ['วิธีการ - ไม่มีมาตรฐาน', 'วิธีการ - กำหนดไม่เหมาะสม'] },
                                                    { cat: 'สิ่งแวดล้อม', subs: ['สิ่งแวดล้อม - ไม่มีมาตรฐาน', 'สิ่งแวดล้อม - กำหนดไม่เหมาะสม'] },
                                                ].map((col) => (
                                                    <div key={col.cat} className="p-3 bg-white rounded-xl border border-slate-200">
                                                        <div className="font-bold text-slate-900 border-b pb-1 mb-2 text-center text-sm">
                                                            {col.cat}
                                                        </div>
                                                        <div className="space-y-1.5">
                                                            {col.subs.map((s) => (
                                                                <label key={s} className="flex items-start gap-1.5 cursor-pointer">
                                                                    <input
                                                                        type="radio"
                                                                        name="root_cause_category"
                                                                        value={s}
                                                                        disabled={ticket.preventive_measure === 'done' || isManager}
                                                                        checked={task2Data.root_cause_category === s}
                                                                        onChange={(e) => setTask2Data('root_cause_category', e.target.value)}
                                                                        className="mt-0.5 text-teal-600 focus:ring-teal-500"
                                                                    />
                                                                    <span className="leading-tight text-slate-700">{s.split(' - ')[1]}</span>
                                                                </label>
                                                            ))}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {ticket.preventive_measure !== 'done' && !isManager && (
                                            <button
                                                type="submit"
                                                disabled={task2Processing}
                                                className="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow transition disabled:opacity-50"
                                            >
                                                💾 บันทึกข้อมูล Task 2
                                            </button>
                                        )}
                                    </form>
                                )}
                            </div>
                        )}

                        {/* Manager Close Preventive Measure */}
                        {ticket.preventive_measure === 'pending_review' && isManager && (
                            <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border-t-4 border-t-blue-500 border-x border-b border-slate-200">
                                <h3 className="text-lg font-bold text-slate-800 mb-2 flex items-center gap-2">
                                    <span className="p-2 bg-blue-50 text-blue-600 rounded-xl">🏁</span>
                                    ปิดมาตรการป้องกันปัญหา (Close Preventive Action)
                                </h3>
                                <p className="text-sm text-slate-600 mb-4">
                                    ช่างได้บันทึกข้อมูลการป้องกันปัญหา (Task 2) แล้ว กรุณาตรวจสอบข้อมูลและกดยืนยันปิดกระบวนการ
                                </p>
                                <button
                                    type="button"
                                    onClick={() => router.put(route('tickets.updateStatus', ticket.id), { action: 'close_preventive_measure' })}
                                    className="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow transition"
                                >
                                    ✅ ยืนยันตรวจสอบและปิดมาตรการป้องกัน
                                </button>
                            </div>
                        )}

                        {/* 6. Requester Details Card */}
                        <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200">
                            <h3 className="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">
                                ข้อมูลผู้แจ้ง (Requester)
                            </h3>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span className="text-xs text-slate-400 font-bold uppercase block">ชื่อผู้แจ้ง</span>
                                    <span className="font-semibold text-slate-800">{ticket.requester_name}</span>
                                    {ticket.actual_user_name && (
                                        <span className="text-xs text-purple-600 block mt-0.5">
                                            (แจ้งแทน: {ticket.actual_user_name})
                                        </span>
                                    )}
                                </div>
                                <div>
                                    <span className="text-xs text-slate-400 font-bold uppercase block">แผนก</span>
                                    <span className="font-semibold text-slate-800">{ticket.department || '-'}</span>
                                </div>
                                <div>
                                    <span className="text-xs text-slate-400 font-bold uppercase block">อีเมล</span>
                                    <span className="font-semibold text-slate-800">{ticket.requester_email}</span>
                                </div>
                                <div>
                                    <span className="text-xs text-slate-400 font-bold uppercase block">เบอร์โทรติดต่อ</span>
                                    <span className="font-semibold text-slate-800">{ticket.requester_phone || '-'}</span>
                                </div>
                                <div className="sm:col-span-2">
                                    <span className="text-xs text-slate-400 font-bold uppercase block">สถานที่ / จุดเกิดปัญหา</span>
                                    <span className="font-semibold text-slate-800">{ticket.location || '-'}</span>
                                </div>
                            </div>
                        </div>

                        {/* 7. CSAT Feedback Card */}
                        {ticket.rating && (
                            <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border-t-4 border-t-yellow-400 border-x border-b border-slate-200">
                                <h3 className="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">
                                    <span className="text-yellow-500">⭐</span> ผลการประเมินความพึงพอใจ (CSAT)
                                </h3>
                                <div className="flex items-center gap-2 mb-3">
                                    <div className="flex text-yellow-400">
                                        {[1, 2, 3, 4, 5].map((star) => (
                                            <svg
                                                key={star}
                                                className={`w-6 h-6 ${star <= ticket.rating ? 'text-yellow-400 fill-current' : 'text-slate-200'}`}
                                                viewBox="0 0 20 20"
                                            >
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        ))}
                                    </div>
                                    <span className="font-black text-slate-800 text-base">{ticket.rating} / 5</span>
                                </div>
                                {ticket.feedback && (
                                    <div className="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-sm text-slate-700 italic">
                                        "{ticket.feedback}"
                                    </div>
                                )}
                            </div>
                        )}

                        {/* 8. Cancel Ticket Button */}
                        {canCancel && (
                            <div className="bg-white rounded-3xl p-6 shadow-sm border border-rose-200">
                                <h3 className="text-base font-bold text-rose-600 mb-1 flex items-center gap-2">
                                    <span>❌</span> ยกเลิกใบงาน (Cancel Ticket)
                                </h3>
                                <p className="text-xs text-slate-500 mb-4">
                                    หากใบงานนี้ไม่ถูกต้อง แจ้งซ้ำ หรือแก้ไขปัญหาได้เองแล้ว สามารถกดยกเลิกได้
                                </p>
                                <button
                                    type="button"
                                    onClick={() => setShowCancelModal(true)}
                                    className="px-5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold rounded-xl border border-rose-200 text-sm transition"
                                >
                                    ขอยกเลิกใบงานนี้
                                </button>
                            </div>
                        )}

                        {/* 9. Real-time Comments Thread */}
                        <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200">
                            <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                                <span className="flex items-center gap-2">
                                    <span className="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">💬</span>
                                    พูดคุย / สอบถามข้อมูลเพิ่มเติม
                                </span>
                                <span className="text-xs text-slate-400 font-normal">
                                    อัปเดตอัตโนมัติทุก 15 วินาที
                                </span>
                            </h3>

                            <div
                                ref={commentsContainerRef}
                                className="space-y-4 mb-6 max-h-96 overflow-y-auto pr-2"
                            >
                                {ticket.comments && ticket.comments.length > 0 ? (
                                    ticket.comments.map((comment) => {
                                        const isMe = comment.user_id === user.id;
                                        return (
                                            <div key={comment.id} className={`flex ${isMe ? 'justify-end' : 'justify-start'}`}>
                                                <div
                                                    className={`p-4 rounded-2xl max-w-[85%] sm:max-w-[75%] border ${
                                                        isMe
                                                            ? 'bg-indigo-50 border-indigo-100 text-indigo-950'
                                                            : 'bg-slate-50 border-slate-200 text-slate-800'
                                                    }`}
                                                >
                                                    <div className="flex justify-between items-center gap-4 mb-1 text-xs font-bold">
                                                        <span className={isMe ? 'text-indigo-700' : 'text-slate-700'}>
                                                            {user.role === 'user' && comment.user?.role !== 'user'
                                                                ? 'เจ้าหน้าที่ไอที (IT Support)'
                                                                : `${comment.user?.name || 'User'} (${comment.user?.role || ''})`}
                                                        </span>
                                                        <span className="text-[10px] text-slate-400 font-normal">
                                                            {formatThaiDateTime(comment.created_at)}
                                                        </span>
                                                    </div>
                                                    <p className="text-sm whitespace-pre-wrap leading-relaxed">{comment.message}</p>
                                                    {comment.attachment_path && (
                                                        <div className="mt-2.5">
                                                            <a
                                                                href={`/storage/${comment.attachment_path}`}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="text-xs text-indigo-600 hover:underline flex items-center gap-1 font-bold"
                                                            >
                                                                📎 ดูไฟล์แนบ
                                                            </a>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="text-center py-8 text-slate-400 text-sm border-2 border-dashed border-slate-100 rounded-2xl">
                                        ยังไม่มีข้อความพูดคุย สามารถพิมพ์สอบถามข้อมูลที่นี่ได้
                                    </div>
                                )}
                            </div>

                            {/* New Comment Input */}
                            {!['closed', 'cancelled'].includes(ticket.status) && (
                                <form onSubmit={submitComment} className="border-t border-slate-100 pt-4 space-y-3">
                                    <textarea
                                        rows={3}
                                        value={commentData.message}
                                        onChange={(e) => setCommentData('message', e.target.value)}
                                        placeholder="พิมพ์ข้อความตอบกลับหรือสอบถามที่นี่..."
                                        className="w-full rounded-2xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 p-3.5"
                                    />
                                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                        <div>
                                            <label
                                                htmlFor="comment-attachment"
                                                className="cursor-pointer text-xs font-bold text-slate-600 hover:text-indigo-600 flex items-center gap-1.5 p-2 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 transition"
                                            >
                                                <span>📷</span>
                                                <span>
                                                    {commentData.attachment ? commentData.attachment.name : 'แนบรูปภาพ (ไม่เกิน 5MB)'}
                                                </span>
                                            </label>
                                            <input
                                                id="comment-attachment"
                                                type="file"
                                                accept="image/*"
                                                capture="environment"
                                                onChange={(e) => {
                                                    const file = e.target.files[0];
                                                    if (file && file.size > 5242880) {
                                                        alert('ขนาดไฟล์เกิน 5MB');
                                                        e.target.value = '';
                                                        setCommentData('attachment', null);
                                                    } else {
                                                        setCommentData('attachment', file);
                                                    }
                                                }}
                                                className="hidden"
                                            />
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={commentProcessing || (!commentData.message && !commentData.attachment)}
                                            className="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm shadow transition disabled:opacity-50"
                                        >
                                            ส่งข้อความ
                                        </button>
                                    </div>
                                </form>
                            )}
                        </div>
                    </div>

                    {/* RIGHT COLUMN: Triage & Timeline */}
                    <div className="space-y-6">
                        {/* 1. Triage / Assignment Card */}
                        <div className="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border-t-4 border-t-indigo-600 border-x border-b border-slate-200">
                            <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <span className="p-2 bg-indigo-50 text-indigo-600 rounded-xl">📋</span>
                                {canTriage ? 'ประเมินและจ่ายงาน (Triage)' : 'ข้อมูลการมอบหมาย (Assignment)'}
                            </h3>

                            {canTriage ? (
                                <form onSubmit={submitTriage} className="space-y-4">
                                    <div>
                                        <label className="block text-xs font-bold text-slate-700 mb-1">
                                            ระดับความเร่งด่วน (Priority) <span className="text-rose-500">*</span>
                                        </label>
                                        <select
                                            required
                                            value={triageData.priority}
                                            onChange={(e) => setTriageData('priority', e.target.value)}
                                            className="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="" disabled>-- เลือกระดับความเร่งด่วน --</option>
                                            <option value="low">ทั่วไป/ต่ำ (Low)</option>
                                            <option value="medium">ปานกลาง (Medium)</option>
                                            <option value="high">สูง (High)</option>
                                            <option value="urgent">ด่วนที่สุด (Urgent)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-bold text-slate-700 mb-1">
                                            ส่งต่อให้ทีม (Escalate To) <span className="text-rose-500">*</span>
                                        </label>
                                        <select
                                            required
                                            value={triageData.escalated_to_team}
                                            onChange={(e) => setTriageData('escalated_to_team', e.target.value)}
                                            className="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="" disabled>-- เลือกการส่งต่องาน --</option>
                                            <option value="None">ไม่ส่งต่อ (Helpdesk จัดการเอง)</option>
                                            <option value="Hardware">ทีม Hardware</option>
                                            <option value="Network">ทีม Network</option>
                                            <option value="Software">ทีม Software</option>
                                        </select>
                                        <p className="text-[11px] text-slate-400 mt-1">
                                            * เมื่อกดส่งต่อ สถานะจะเปลี่ยนเป็น "มอบหมายแล้ว" โดยอัตโนมัติ
                                        </p>
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={triageProcessing}
                                        className="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow transition disabled:opacity-50 text-sm"
                                    >
                                        บันทึกการประเมิน
                                    </button>
                                </form>
                            ) : (
                                <div className="space-y-3 text-sm">
                                    <div>
                                        <span className="text-xs text-slate-400 font-bold uppercase block">สถานะใบงาน</span>
                                        <span className="font-bold text-indigo-700">{ticket.status_label}</span>
                                    </div>
                                    <div>
                                        <span className="text-xs text-slate-400 font-bold uppercase block">ระดับความเร่งด่วน</span>
                                        <span className={`font-bold ${ticket.priority === 'urgent' ? 'text-rose-600' : 'text-slate-800'}`}>
                                            {ticket.priority_label}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-xs text-slate-400 font-bold uppercase block">ทีมที่รับผิดชอบ</span>
                                        <span className="font-bold text-slate-800">{ticket.assigned_team_label}</span>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* 2. Task 1 Timeline */}
                        <div className="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200">
                            <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                                <span>⏱️</span> ไทม์ไลน์สถานะ (Timeline)
                            </h3>

                            <div className="relative pl-3 space-y-6">
                                <div className="absolute top-2 bottom-2 left-4 w-0.5 bg-slate-200" />

                                {/* Step 1: Open */}
                                <div className="relative flex items-start gap-4">
                                    <div className="relative flex items-center justify-center w-3 h-3 mt-1.5 z-10 ring-4 ring-white rounded-full bg-blue-500">
                                        {['pending', 'assigned'].includes(ticket.status) && (
                                            <span className="absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75 animate-ping" />
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-sm font-bold text-slate-800">1. เปิดเคส / รอดำเนินการ</p>
                                        <p className="text-xs text-slate-500">{formatThaiDateTime(ticket.created_at)}</p>
                                        <p className="text-xs text-indigo-600 font-medium">โดย: {ticket.requester_name}</p>
                                    </div>
                                </div>

                                {/* Step 2: Analyzing */}
                                <div className={`relative flex items-start gap-4 ${!ticket.analyzing_at ? 'opacity-40' : ''}`}>
                                    <div className={`relative flex items-center justify-center w-3 h-3 mt-1.5 z-10 ring-4 ring-white rounded-full ${ticket.analyzing_at ? 'bg-purple-500' : 'bg-slate-300'}`}>
                                        {ticket.status === 'analyzing' && (
                                            <span className="absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75 animate-ping" />
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-sm font-bold text-slate-800">2. สืบสภาพ และวิเคราะห์สาเหตุ</p>
                                        {ticket.analyzing_at ? (
                                            <>
                                                <p className="text-xs text-slate-500">เริ่ม: {formatThaiDateTime(ticket.analyzing_at)}</p>
                                                {ticket.in_progress_at && (
                                                    <p className="text-xs text-slate-500">จบ: {formatThaiDateTime(ticket.in_progress_at)}</p>
                                                )}
                                                {ticket.analyzing_by && (
                                                    <p className="text-xs text-purple-600 font-medium">บันทึกโดย: {ticket.analyzing_by.name}</p>
                                                )}
                                            </>
                                        ) : (
                                            <p className="text-xs text-slate-400">รอการดำเนินการ</p>
                                        )}
                                    </div>
                                </div>

                                {/* Step 3: In Progress & Resolution */}
                                <div className={`relative flex items-start gap-4 ${!ticket.in_progress_at ? 'opacity-40' : ''}`}>
                                    <div className={`relative flex items-center justify-center w-3 h-3 mt-1.5 z-10 ring-4 ring-white rounded-full ${ticket.in_progress_at ? 'bg-indigo-500' : 'bg-slate-300'}`}>
                                        {ticket.status === 'in_progress' && (
                                            <span className="absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75 animate-ping" />
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-sm font-bold text-slate-800">3. กำลังแก้ไข และส่งมอบงาน</p>
                                        {ticket.in_progress_at ? (
                                            <>
                                                <p className="text-xs text-slate-500">เริ่ม: {formatThaiDateTime(ticket.in_progress_at)}</p>
                                                {ticket.resolved_at && (
                                                    <p className="text-xs text-emerald-600 font-bold">ส่งมอบ: {formatThaiDateTime(ticket.resolved_at)}</p>
                                                )}
                                                {ticket.resolved_by && (
                                                    <p className="text-xs text-emerald-600 font-medium">ส่งงานโดย: {ticket.resolved_by.name}</p>
                                                )}
                                            </>
                                        ) : (
                                            <p className="text-xs text-slate-400">รอการดำเนินการ</p>
                                        )}
                                    </div>
                                </div>

                                {/* Step 4: Approved */}
                                <div className={`relative flex items-start gap-4 ${!ticket.approved_at ? 'opacity-40' : ''}`}>
                                    <div className={`relative flex items-center justify-center w-3 h-3 mt-1.5 z-10 ring-4 ring-white rounded-full ${ticket.approved_at ? 'bg-emerald-500' : 'bg-slate-300'}`}>
                                        {ticket.status === 'resolved' && (
                                            <span className="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping" />
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-sm font-bold text-slate-800">
                                            {ticket.approved_at ? '4. ผู้แจ้งรับทราบการแก้ไข' : '4. รอผู้แจ้งรับงาน'}
                                        </p>
                                        {ticket.approved_at ? (
                                            <>
                                                <p className="text-xs text-slate-500">{formatThaiDateTime(ticket.approved_at)}</p>
                                                {ticket.approved_by && (
                                                    <p className="text-xs text-emerald-600 font-medium">รับงานโดย: {ticket.approved_by.name}</p>
                                                )}
                                            </>
                                        ) : (
                                            <p className="text-xs text-slate-400">รอการดำเนินการ</p>
                                        )}
                                    </div>
                                </div>

                                {/* Step 5: Closed */}
                                <div className={`relative flex items-start gap-4 ${!ticket.closed_at ? 'opacity-40' : ''}`}>
                                    <div className={`relative flex items-center justify-center w-3 h-3 mt-1.5 z-10 ring-4 ring-white rounded-full ${ticket.closed_at ? 'bg-slate-800' : 'bg-slate-300'}`}>
                                        {ticket.status === 'approved' && (
                                            <span className="absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75 animate-ping" />
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-sm font-bold text-slate-800">
                                            {ticket.closed_at ? '5. ปิดใบงานเรียบร้อย' : '5. รอหัวหน้าปิดใบงาน'}
                                        </p>
                                        {ticket.closed_at ? (
                                            <>
                                                <p className="text-xs text-slate-500">{formatThaiDateTime(ticket.closed_at)}</p>
                                                {ticket.closed_by && (
                                                    <p className="text-xs text-slate-800 font-medium">ปิดโดย: {ticket.closed_by.name}</p>
                                                )}
                                            </>
                                        ) : (
                                            <p className="text-xs text-slate-400">รอการดำเนินการ</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* 3. Task 1 Duration Summary */}
                        {user.role !== 'user' && (
                            <div className="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
                                <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                                    สรุปเวลาการทำงาน Task 1
                                </h4>
                                <div className="space-y-2 text-xs sm:text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">แจ้งเมื่อ:</span>
                                        <span className="font-semibold text-slate-800">{formatThaiDateTime(ticket.created_at)}</span>
                                    </div>
                                    {totalDurationTask1 && (
                                        <div className="flex justify-between pt-2 border-t border-slate-100">
                                            <span className="text-emerald-700 font-bold">เวลารวมทั้งหมด:</span>
                                            <span className="text-emerald-700 font-black text-sm">{totalDurationTask1}</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* 4. Task 2 Timeline (if required) */}
                        {ticket.requires_preventive_measure && user.role !== 'user' && (
                            <div className="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200">
                                <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                                    <span>🛡️</span> ไทม์ไลน์สถานะ Task 2
                                </h3>
                                <div className="space-y-4 text-xs sm:text-sm">
                                    <div className="p-3 bg-slate-50 rounded-xl">
                                        <span className="text-slate-500 block">เปิด P-CAR เมื่อ:</span>
                                        <span className="font-bold text-slate-800">{formatThaiDateTime(ticket.pcar_opened_at)}</span>
                                    </div>
                                    <div className="p-3 bg-slate-50 rounded-xl">
                                        <span className="text-slate-500 block">สถานะ Task 2:</span>
                                        <span className="font-bold text-teal-700">{ticket.task2_status_label || 'รอดำเนินการ'}</span>
                                    </div>
                                    {ticket.pcar_closed_at && (
                                        <div className="p-3 bg-teal-50 rounded-xl text-teal-900">
                                            <span className="block text-xs font-bold">ปิดมาตรการเมื่อ:</span>
                                            <span className="font-black text-sm">{formatThaiDateTime(ticket.pcar_closed_at)}</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Modal 1: Image Zoom */}
            <Modal show={!!previewImage} onClose={() => setPreviewImage(null)} maxWidth="2xl">
                <div className="p-4 bg-slate-900 flex flex-col items-center">
                    <img src={previewImage} alt="Enlarged" className="max-h-[80vh] w-auto rounded-xl object-contain" />
                    <button
                        type="button"
                        onClick={() => setPreviewImage(null)}
                        className="mt-4 px-6 py-2 bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl text-sm transition"
                    >
                        ปิดหน้าต่าง
                    </button>
                </div>
            </Modal>

            {/* Modal 2: CSAT Modal */}
            <Modal show={showCsatModal} onClose={() => setShowCsatModal(false)} maxWidth="md">
                <div className="p-6 sm:p-8 bg-white rounded-3xl">
                    <h3 className="text-xl font-black text-slate-900 text-center mb-1">
                        คุณพึงพอใจกับการบริการมากน้อยเพียงใด?
                    </h3>
                    <p className="text-xs text-slate-500 text-center mb-6">
                        การประเมินของคุณจะช่วยให้ทีม IT พัฒนาการบริการให้ดียิ่งขึ้น
                    </p>

                    {/* Star Rating */}
                    <div className="flex justify-center gap-2 mb-6">
                        {[1, 2, 3, 4, 5].map((star) => (
                            <button
                                key={star}
                                type="button"
                                onClick={() => setCsatRating(star)}
                                onMouseEnter={() => setCsatHover(star)}
                                onMouseLeave={() => setCsatHover(0)}
                                className="focus:outline-none transform hover:scale-110 transition-transform"
                            >
                                <svg
                                    className={`w-10 h-10 transition-colors duration-150 ${
                                        (csatHover || csatRating) >= star ? 'text-yellow-400 fill-current' : 'text-slate-200'
                                    }`}
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </button>
                        ))}
                    </div>

                    <div className="mb-6">
                        <label className="block text-xs font-bold text-slate-700 mb-1">
                            ข้อเสนอแนะเพิ่มเติม (ถ้ามี)
                        </label>
                        <textarea
                            rows={3}
                            value={csatFeedback}
                            onChange={(e) => setCsatFeedback(e.target.value)}
                            placeholder="พิมพ์ข้อเสนอแนะ หรือคำชมเชยที่นี่..."
                            className="w-full rounded-2xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                        />
                    </div>

                    <div className="flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={() => setShowCsatModal(false)}
                            className="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-sm transition"
                        >
                            ยกเลิก
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                router.put(
                                    route('tickets.updateStatus', ticket.id),
                                    {
                                        action: 'accept_resolution',
                                        rating: csatRating,
                                        feedback: csatFeedback,
                                    },
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => setShowCsatModal(false),
                                    }
                                );
                            }}
                            className="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-sm shadow transition"
                        >
                            ยืนยันและส่งคะแนน
                        </button>
                    </div>
                </div>
            </Modal>

            {/* Modal 3: Cancel Ticket Modal */}
            <Modal show={showCancelModal} onClose={() => setShowCancelModal(false)} maxWidth="md">
                <div className="p-6 sm:p-8 bg-white rounded-3xl">
                    <h3 className="text-xl font-black text-rose-600 mb-2">
                        ยืนยันการยกเลิกใบงาน
                    </h3>
                    <p className="text-xs text-slate-500 mb-4">
                        กรุณาระบุเหตุผลการยกเลิกใบงาน เพื่อเก็บเป็นประวัติในระบบ
                    </p>

                    <div className="mb-4">
                        <label className="block text-xs font-bold text-slate-700 mb-1">
                            เหตุผลการยกเลิก <span className="text-rose-500">*</span>
                        </label>
                        <textarea
                            rows={3}
                            required
                            value={cancelReason}
                            onChange={(e) => setCancelReason(e.target.value)}
                            placeholder="ระบุเหตุผล เช่น แจ้งซ้ำ หรือแก้ปัญหาได้เองแล้ว..."
                            className="w-full rounded-2xl border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500"
                        />
                    </div>

                    {['analyzing', 'in_progress'].includes(ticket.status) && (
                        <div className="mb-4 p-3 bg-amber-50 border-l-4 border-amber-400 text-amber-800 text-xs rounded-r-xl">
                            <strong>⚠️ คำเตือน:</strong> เคสนี้มีช่างกำลังดำเนินการอยู่ การยกเลิกจะทำให้การปฏิบัติงานถูกระงับทันที
                        </div>
                    )}

                    <div className="flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={() => setShowCancelModal(false)}
                            className="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-sm transition"
                        >
                            กลับ
                        </button>
                        <button
                            type="button"
                            disabled={!cancelReason.trim()}
                            onClick={() => {
                                router.put(
                                    route('tickets.updateStatus', ticket.id),
                                    {
                                        action: 'cancel',
                                        cancellation_reason: cancelReason,
                                    },
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => setShowCancelModal(false),
                                    }
                                );
                            }}
                            className="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-sm shadow transition disabled:opacity-50"
                        >
                            ยืนยันยกเลิกใบงาน
                        </button>
                    </div>
                </div>
            </Modal>
        </>
    );
}
