import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';

const KEY_MAP = {
    name: 'ชื่อ',
    name_th: 'ชื่อ (ไทย)',
    short_name: 'ชื่อย่อ',
    code: 'รหัสทีม',
    is_active: 'สถานะการใช้งาน',
    status: 'สถานะ',
    description: 'รายละเอียด',
    manager_name: 'หัวหน้าทีม',
    manager_id: 'หัวหน้าทีม',
    member_ids: 'สมาชิกในทีม',
    team_members_delta: 'สมาชิกในทีม',
    email_domains: 'โดเมนอีเมล',
    role: 'ระดับสิทธิ์',
    company: 'บริษัท',
    department: 'แผนก/หน่วยงาน',
    phone: 'เบอร์โทรศัพท์',
    priority: 'ความเร่งด่วน',
    hours: 'จำนวนชั่วโมง',
    sla_type: 'ประเภท SLA',
    date: 'วันที่',
    action_note: 'หมายเหตุ',
    reason: 'เหตุผล',
    line_channel_access_token: 'LINE Access Token',
    line_channel_secret: 'LINE Secret',
    smtp_host: 'SMTP Host',
    smtp_port: 'SMTP Port',
    smtp_username: 'SMTP Username / อีเมลผู้ส่ง',
    smtp_encryption: 'การเข้ารหัส SMTP',
    smtp_from_address: 'อีเมลผู้ส่ง (From Address)',
};

const ROLE_MAP = {
    administrator: 'System Administrator (ผู้ดูแลระบบ)',
    manager: 'IT Service Manager (Tier 3)',
    technician: 'Technical Specialist (Tier 2)',
    helpdesk: 'Service Desk Agent (Tier 1)',
    user: 'End User (ผู้ใช้งานทั่วไป)',
    manager_software: 'Software Manager (Tier 3)',
    manager_network_hardware: 'Network & Hardware Manager (Tier 3)',
    team_hardware: 'Team Hardware (Tier 2)',
    team_network: 'Team Network (Tier 2)',
    team_software: 'Team Software (Tier 2)',
};

const IGNORED_KEYS = ['id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at', 'remember_token', 'password'];

function getActionBadge(action = '') {
    if (action.includes('impersonate.leave')) {
        return { label: 'คืนสิทธิ์ (Leave)', className: 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20' };
    }
    if (action.includes('impersonate')) {
        return { label: 'จำลองสิทธิ์', className: 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20' };
    }
    if (action.includes('logout')) {
        return { label: 'ออกจากระบบ', className: 'bg-slate-100 text-slate-700 ring-1 ring-slate-400/20' };
    }
    if (action.includes('force_reset_password') || action.includes('force-reset-password')) {
        return { label: 'รีเซ็ตรหัสผ่าน', className: 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' };
    }
    if (action.includes('DEACTIVATE') || action.toLowerCase().includes('deactivate')) {
        return { label: 'เปลี่ยนสถานะเป็น Inactive', className: 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' };
    }
    if (action.includes('settings.update')) {
        return { label: 'แก้ไขข้อมูล', className: 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20' };
    }
    if (action.includes('POST') || action === 'created') {
        return { label: 'เพิ่มข้อมูล', className: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' };
    }
    if (action.includes('PUT') || action.includes('PATCH') || action === 'updated') {
        return { label: 'แก้ไขข้อมูล', className: 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20' };
    }
    if (action.includes('DELETE') || action === 'deleted') {
        return { label: 'ลบข้อมูล', className: 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20' };
    }
    return { label: action, className: 'bg-gray-100 text-gray-800' };
}

function getTargetInfo(targetType = '', targetId = '', targetUser = null, targetCompanyName = null, targetTeamName = null) {
    let text = targetType.split('\\').pop() || targetType;
    let isUser = false;
    let isCompany = false;
    let isTeam = false;

    if (targetType.includes('categories')) text = 'หมวดหมู่ปัญหา';
    else if (targetType.includes('companies')) {
        text = 'ข้อมูลบริษัท';
        isCompany = true;
    }
    else if (targetType.includes('teams')) {
        text = 'ทีมงาน / สายงาน';
        isTeam = true;
    }
    else if (targetType.includes('users')) {
        text = 'ผู้ใช้งาน';
        isUser = true;
    }
    else if (targetType.includes('departments')) text = 'แผนก/หน่วยงาน';
    else if (targetType.includes('slas')) text = 'SLA';
    else if (targetType.includes('holidays')) text = 'วันหยุดนักขัตฤกษ์';
    else if (targetType.includes('settings')) text = 'ตั้งค่าระบบ';
    else if (targetType.includes('impersonate')) {
        text = 'บัญชีผู้ใช้';
        isUser = true;
    }
    else if (targetType.includes('logout')) text = 'เซสชันการใช้งาน';

    let displayId = targetId;
    if (displayId) {
        try {
            const parsed = typeof displayId === 'string' ? JSON.parse(displayId) : displayId;
            if (parsed && parsed.id) displayId = parsed.id;
        } catch (e) {
            if (String(displayId).length > 15) {
                displayId = String(displayId).substring(0, 15) + '...';
            }
        }
    }

    return {
        text,
        displayId,
        targetUser: isUser ? targetUser : null,
        targetCompanyName: isCompany ? targetCompanyName : null,
        targetTeamName: isTeam ? targetTeamName : null,
    };
}

function formatValue(val, key) {
    if (val === null || val === undefined || val === '') return 'ว่างเปล่า';
    if (key === 'is_active' || key === 'status') {
        return val == 1 || val === true ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
    }
    if (typeof val === 'object') return JSON.stringify(val);
    return String(val);
}

function LogChangesDisplay({ log }) {
    if (log.action && (log.action.includes('force_reset_password') || log.action.includes('force-reset-password'))) {
        return (
            <span className="text-amber-800 font-medium text-xs flex items-center gap-1.5">
                <svg className="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                ส่งลิงก์รีเซ็ตรหัสผ่านใหม่ไปยังอีเมลของผู้ใช้งานเรียบร้อยแล้ว
            </span>
        );
    }

    if (log.target_type && (log.target_type.includes('logout') || (log.action && log.action.includes('logout')))) {
        return <span className="text-gray-700 font-medium">ออกจากระบบสำเร็จ</span>;
    }

    if (log.target_type && (log.target_type.includes('impersonate') || (log.action && log.action.includes('impersonate')))) {
        if (log.action && log.action.includes('impersonate.leave')) {
            const tu = log.target_user || (log.changes?.impersonated_user_name ? {
                name: log.changes.impersonated_user_name,
            } : null);

            return (
                <div className="text-gray-700 font-medium text-xs flex items-center gap-1.5 flex-wrap">
                    {tu?.name ? (
                        <>
                            <span className="text-gray-500 font-normal">ออกจากสิทธิ์ของ:</span>
                            <span className="text-purple-700 font-bold">{tu.name}</span>
                            <span className="text-gray-500 font-normal">กลับสู่บัญชีหลัก (Admin)</span>
                        </>
                    ) : (
                        <span>กลับสู่บัญชีหลัก (Admin)</span>
                    )}
                </div>
            );
        }

        const tu = log.target_user || (log.changes?.impersonated_user_name ? {
            name: log.changes.impersonated_user_name,
            email: log.changes.impersonated_user_email,
            role: log.changes.impersonated_user_role,
            department: log.changes.impersonated_user_department,
        } : null);

        if (tu) {
            return (
                <div className="space-y-1">
                    <div className="text-gray-900 font-semibold flex items-center gap-1.5 flex-wrap">
                        <span className="text-gray-500 font-normal">เข้าสู่ระบบด้วยสิทธิ์ของ:</span>
                        <span className="text-indigo-700 font-bold">{tu.name}</span>
                        {log.target_id && <span className="text-gray-400 font-mono text-[11px]">(ID #{log.target_id})</span>}
                    </div>
                    <div className="flex flex-wrap items-center gap-1.5 text-xs">
                        <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20">
                            {ROLE_MAP[tu.role] || tu.role}
                        </span>
                        {tu.department && (
                            <span className="text-gray-600 text-[11px] font-medium">• แผนก {tu.department}</span>
                        )}
                        {tu.email && (
                            <span className="text-gray-400 font-mono text-[11px]">({tu.email})</span>
                        )}
                    </div>
                </div>
            );
        }

        return (
            <span className="text-gray-700">
                เข้าสู่ระบบด้วยสิทธิ์ของ: <span className="font-bold text-indigo-700">ID #{log.target_id}</span>
            </span>
        );
    }

    if (log.changes && typeof log.changes === 'object') {
        const keys = Object.keys(log.changes).filter((k) => !IGNORED_KEYS.includes(k));

        if (keys.length === 0) {
            return <em className="text-gray-400">ไม่มีการเปลี่ยนแปลงข้อมูลสำคัญ</em>;
        }

        return (
            <div className="space-y-2">
                {keys.map((key) => {
                    const keyLabel = KEY_MAP[key] || key;

                    // 1. Team Members Delta (SOC 2 / ISO 27001 Standard)
                    if (key === 'team_members_delta' && typeof log.changes[key] === 'object' && log.changes[key] !== null) {
                        const delta = log.changes[key];
                        const hasAdded = Array.isArray(delta.added) && delta.added.length > 0;
                        const hasRemoved = Array.isArray(delta.removed) && delta.removed.length > 0;

                        if (!hasAdded && !hasRemoved) return null;

                        return (
                            <div key={key} className="text-xs border-b border-gray-100 last:border-0 pb-2 last:pb-0">
                                <div className="font-bold text-blue-700 mb-1 flex items-center gap-1.5">
                                    <svg className="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    สมาชิกในทีม:
                                </div>
                                <div className="space-y-1.5 pl-2 border-l-2 border-indigo-200">
                                    {hasAdded && (
                                        <div>
                                            <div className="text-[10px] font-bold text-emerald-700 uppercase flex items-center gap-1 mb-1">
                                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block shrink-0"></span>
                                                เพิ่มสมาชิกเข้าทีม (+):
                                            </div>
                                            <div className="flex flex-wrap gap-1.5">
                                                {delta.added.map((m) => (
                                                    <span key={m.id} className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-800 ring-1 ring-emerald-600/20">
                                                        <span className="font-semibold">{m.name}</span>
                                                        {m.email && <span className="text-emerald-600/70 font-mono text-[10px]">({m.email})</span>}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                    {hasRemoved && (
                                        <div>
                                            <div className="text-[10px] font-bold text-rose-700 uppercase flex items-center gap-1 mb-1">
                                                <span className="w-1.5 h-1.5 rounded-full bg-rose-500 inline-block shrink-0"></span>
                                                นำสมาชิกออกจากทีม (-):
                                            </div>
                                            <div className="flex flex-wrap gap-1.5">
                                                {delta.removed.map((m) => (
                                                    <span key={m.id} className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium bg-rose-50 text-rose-800 ring-1 ring-rose-600/20">
                                                        <span className="font-semibold">{m.name}</span>
                                                        {m.email && <span className="text-rose-600/70 font-mono text-[10px]">({m.email})</span>}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    }

                    // 2. Legacy / Fallback member_ids formatting
                    if (key === 'member_ids') {
                        const resolvedList = log.legacy_members_resolved || [];
                        return (
                            <div key={key} className="text-xs border-b border-gray-100 last:border-0 pb-2 last:pb-0">
                                <div className="font-bold text-blue-700 mb-1 flex items-center gap-1.5">
                                    <svg className="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    สมาชิกในทีม:
                                </div>
                                {resolvedList.length > 0 ? (
                                    <div className="flex flex-wrap gap-1.5 pl-2 border-l-2 border-blue-200">
                                        {resolvedList.map((m) => (
                                            <span key={m.id} className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-800 ring-1 ring-blue-600/20">
                                                <span className="font-semibold">{m.name}</span>
                                                {m.email && <span className="text-blue-600/70 font-mono text-[10px]">({m.email})</span>}
                                            </span>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="pl-2 border-l-2 border-gray-200 text-gray-600 font-mono">{formatValue(log.changes[key], key)}</div>
                                )}
                            </div>
                        );
                    }

                    // 3. Manager ID (Legacy / Resolved)
                    if (key === 'manager_id') {
                        const displayNew = log.manager_name_resolved || formatValue(log.changes[key], key);
                        const hasOld = log.old_values && Object.prototype.hasOwnProperty.call(log.old_values, key);
                        const displayOld = log.old_manager_name_resolved || (hasOld ? formatValue(log.old_values[key], key) : null);

                        if (hasOld) {
                            return (
                                <div key={key} className="text-xs border-b border-gray-100 last:border-0 pb-1.5 last:pb-0">
                                    <div className="font-bold text-blue-700 mb-0.5">หัวหน้าทีม:</div>
                                    <div className="pl-2 border-l-2 border-gray-200 space-y-0.5">
                                        <div>
                                            <span className="text-gray-400 uppercase text-[10px]">เดิม: </span>
                                            <span className="text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded font-medium break-all">{displayOld}</span>
                                        </div>
                                        <div>
                                            <span className="text-gray-400 uppercase text-[10px]">ใหม่: </span>
                                            <span className="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-medium break-all">{displayNew}</span>
                                        </div>
                                    </div>
                                </div>
                            );
                        }

                        return (
                            <div key={key} className="text-xs border-b border-gray-100 last:border-0 pb-1 last:pb-0">
                                <span className="font-semibold text-gray-700">หัวหน้าทีม: </span>
                                <span className="text-gray-800 font-medium break-all">{displayNew}</span>
                            </div>
                        );
                    }

                    const newVal = formatValue(log.changes[key], key);
                    const hasOld = log.old_values && Object.prototype.hasOwnProperty.call(log.old_values, key);
                    const oldVal = hasOld ? formatValue(log.old_values[key], key) : null;

                    if (hasOld) {
                        return (
                            <div key={key} className="text-xs border-b border-gray-100 last:border-0 pb-1.5 last:pb-0">
                                <div className="font-bold text-blue-700 mb-0.5">{keyLabel}:</div>
                                <div className="pl-2 border-l-2 border-gray-200 space-y-0.5">
                                    <div>
                                        <span className="text-gray-400 uppercase text-[10px]">เดิม: </span>
                                        <span className="text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded font-mono break-all">{oldVal}</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-400 uppercase text-[10px]">ใหม่: </span>
                                        <span className="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-medium font-mono break-all">{newVal}</span>
                                    </div>
                                </div>
                            </div>
                        );
                    }

                    return (
                        <div key={key} className="text-xs border-b border-gray-100 last:border-0 pb-1 last:pb-0">
                            <span className="font-semibold text-gray-700">{keyLabel}: </span>
                            <span className="text-gray-600 font-mono break-all">{newVal}</span>
                        </div>
                    );
                })}
            </div>
        );
    }

    if (log.changes) {
        return (
            <pre className="text-[10px] font-mono max-w-full overflow-x-auto whitespace-pre-wrap text-gray-700">
                {typeof log.changes === 'string' ? log.changes : JSON.stringify(log.changes, null, 2)}
            </pre>
        );
    }

    return <em className="text-gray-400 text-xs">ไม่มีรายละเอียด</em>;
}

export default function Index({ logs, users = [], filters = {} }) {
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');
    const [userId, setUserId] = useState(filters.user_id || '');
    const [actionFilter, setActionFilter] = useState(filters.action_filter || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route('audit_logs.index'),
            {
                date_from: dateFrom,
                date_to: dateTo,
                user_id: userId,
                action_filter: actionFilter,
            },
            { preserveState: true }
        );
    };

    const handleClear = () => {
        setDateFrom('');
        setDateTo('');
        setUserId('');
        setActionFilter('');
        router.get(route('audit_logs.index'));
    };

    const exportUrl = route('audit_logs.index', {
        date_from: dateFrom,
        date_to: dateTo,
        user_id: userId,
        action_filter: actionFilter,
        export: 'csv',
    });

    return (
        <>
            <Head title="ประวัติการใช้งานระบบ (Audit Logs)" />

            <div className="py-6 sm:py-8 bg-gray-50 min-h-screen">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-indigo-50 border border-indigo-100 text-indigo-600 rounded-xl shadow-xs">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h1 className="text-2xl font-black text-gray-900 tracking-tight">
                                    ประวัติการใช้งานระบบ (Audit Logs)
                                </h1>
                                <p className="text-sm text-gray-500 mt-0.5">
                                    ติดตามการเปลี่ยนแปลง เพิ่ม แก้ไข และลบข้อมูลทั้งหมดภายในระบบ
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Filter Card */}
                    <div className="bg-white shadow-sm sm:rounded-2xl border border-gray-100 p-5">
                        <form onSubmit={handleSearch} className="flex flex-col md:flex-row md:items-end gap-3">
                            <div className="w-full md:w-1/4">
                                <label className="block text-xs font-semibold text-gray-700 mb-1">ตั้งแต่วันที่</label>
                                <input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                />
                            </div>

                            <div className="w-full md:w-1/4">
                                <label className="block text-xs font-semibold text-gray-700 mb-1">ถึงวันที่</label>
                                <input
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                />
                            </div>

                            <div className="w-full md:w-1/4">
                                <label className="block text-xs font-semibold text-gray-700 mb-1">ผู้ใช้งาน</label>
                                <select
                                    value={userId}
                                    onChange={(e) => setUserId(e.target.value)}
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                >
                                    <option value="">ทั้งหมด</option>
                                    {users.map((u) => (
                                        <option key={u.id} value={u.id}>
                                            {u.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="w-full md:w-1/4">
                                <label className="block text-xs font-semibold text-gray-700 mb-1">การกระทำ</label>
                                <select
                                    value={actionFilter}
                                    onChange={(e) => setActionFilter(e.target.value)}
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                >
                                    <option value="">ทั้งหมด</option>
                                    <option value="POST">เพิ่มข้อมูล</option>
                                    <option value="PUT">แก้ไขข้อมูล</option>
                                    <option value="DELETE">ลบข้อมูล</option>
                                    <option value="DEACTIVATE">เปลี่ยนสถานะเป็น Inactive</option>
                                </select>
                            </div>

                            <div className="flex gap-2 w-full md:w-auto shrink-0 pt-2 md:pt-0">
                                <button
                                    type="submit"
                                    className="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-indigo-700 shadow-xs transition"
                                >
                                    ค้นหา
                                </button>
                                <button
                                    type="button"
                                    onClick={handleClear}
                                    className="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-bold text-xs text-gray-700 uppercase tracking-wider shadow-xs hover:bg-gray-50 transition"
                                >
                                    ล้าง
                                </button>
                                <a
                                    href={exportUrl}
                                    className="flex-1 md:flex-none inline-flex justify-center items-center gap-1.5 px-4 py-2.5 bg-emerald-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-emerald-700 shadow-xs transition"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    CSV
                                </a>
                            </div>
                        </form>
                    </div>

                    {/* Table Card */}
                    <div className="bg-white shadow-sm sm:rounded-2xl border border-gray-100 overflow-hidden">
                        {logs.data && logs.data.length > 0 ? (
                            <>
                                {/* Mobile Card View */}
                                <div className="md:hidden divide-y divide-gray-100">
                                    {logs.data.map((log) => {
                                        const badge = getActionBadge(log.action);
                                        const target = getTargetInfo(log.target_type, log.target_id, log.target_user, log.target_company_name, log.target_team_name);
                                        const createdDate = new Date(log.created_at);
                                        const dateStr = createdDate.toLocaleDateString('th-TH', {
                                            day: 'numeric',
                                            month: 'short',
                                            year: 'numeric',
                                        });
                                        const timeStr = createdDate.toLocaleTimeString('th-TH', {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        });

                                        return (
                                            <div key={log.id} className="p-4 space-y-3">
                                                <div className="flex justify-between items-start gap-2">
                                                    <div>
                                                        <div className="text-xs font-bold text-gray-900">{dateStr}</div>
                                                        <div className="text-[11px] text-gray-400">{timeStr} น.</div>
                                                    </div>
                                                    <span className={`px-2.5 py-0.5 rounded-full text-[11px] font-bold ${badge.className}`}>
                                                        {badge.label}
                                                    </span>
                                                </div>

                                                <div className="space-y-1 text-xs">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 font-bold text-[10px] flex items-center justify-center shrink-0">
                                                            {(log.display_actor_name || log.user?.name || 'S').substring(0, 1)}
                                                        </div>
                                                        <span className="text-gray-700">
                                                            <span className="text-gray-400">โดย: </span>
                                                            <span className="font-semibold">{log.display_actor_name || log.user?.name || 'System'}</span>
                                                        </span>
                                                    </div>

                                                    <div className="text-gray-700">
                                                        <span className="text-gray-400">เป้าหมาย: </span>
                                                        <span className="font-semibold">{target.text}</span>
                                                        {target.targetUser?.name ? (
                                                            <span className="text-indigo-600 font-medium ml-1">
                                                                : {target.targetUser.name} <span className="text-gray-400 font-mono text-[11px]">#{target.displayId || target.targetUser.id}</span>
                                                            </span>
                                                        ) : target.targetCompanyName ? (
                                                            <span className="text-indigo-600 font-medium ml-1">
                                                                : {target.targetCompanyName} <span className="text-gray-400 font-mono text-[11px]">#{target.displayId}</span>
                                                            </span>
                                                        ) : target.targetTeamName ? (
                                                            <span className="text-indigo-600 font-medium ml-1">
                                                                : {target.targetTeamName} <span className="text-gray-400 font-mono text-[11px]">#{target.displayId}</span>
                                                            </span>
                                                        ) : target.displayId ? (
                                                            <span className="text-gray-400 font-mono"> #{target.displayId}</span>
                                                        ) : null}
                                                    </div>
                                                </div>

                                                <div className="bg-gray-50 rounded-xl p-3">
                                                    <div className="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">
                                                        รายละเอียดการเปลี่ยนแปลง
                                                    </div>
                                                    <LogChangesDisplay log={log} />
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>

                                {/* Desktop Table View */}
                                <div className="hidden md:block overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50 border-b border-gray-100">
                                            <tr>
                                                <th className="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">เวลา</th>
                                                <th className="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">ผู้ใช้งาน</th>
                                                <th className="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">การกระทำ</th>
                                                <th className="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">เป้าหมาย</th>
                                                <th className="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-100 text-sm">
                                            {logs.data.map((log) => {
                                                const badge = getActionBadge(log.action);
                                                const target = getTargetInfo(log.target_type, log.target_id, log.target_user, log.target_company_name, log.target_team_name);
                                                const createdDate = new Date(log.created_at);
                                                const dateStr = createdDate.toLocaleDateString('th-TH', {
                                                    day: 'numeric',
                                                    month: 'short',
                                                    year: 'numeric',
                                                });
                                                const timeStr = createdDate.toLocaleTimeString('th-TH', {
                                                    hour: '2-digit',
                                                    minute: '2-digit',
                                                });

                                                return (
                                                    <tr key={log.id} className="hover:bg-gray-50/80 transition-colors">
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div className="text-xs font-bold text-gray-900">{dateStr}</div>
                                                            <div className="text-[11px] text-gray-400 mt-0.5">{timeStr} น.</div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div className="flex items-center gap-2">
                                                                <div className="w-7 h-7 rounded-full bg-gradient-to-tr from-indigo-500 to-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                                                    {(log.display_actor_name || log.user?.name || 'S').substring(0, 1)}
                                                                </div>
                                                                <span className="font-semibold text-gray-900 text-xs">
                                                                    {log.display_actor_name || log.user?.name || 'System'}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-bold ${badge.className}`}>
                                                                {badge.label}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span className="font-bold text-gray-800 text-xs">{target.text}</span>
                                                            {target.targetUser?.name ? (
                                                                <span className="text-indigo-600 font-medium text-xs ml-1">
                                                                    : {target.targetUser.name} <span className="text-gray-400 font-mono text-[11px]">#{target.displayId || target.targetUser.id}</span>
                                                                </span>
                                                            ) : target.targetCompanyName ? (
                                                                <span className="text-indigo-600 font-medium text-xs ml-1">
                                                                    : {target.targetCompanyName} <span className="text-gray-400 font-mono text-[11px]">#{target.displayId}</span>
                                                                </span>
                                                            ) : target.targetTeamName ? (
                                                                <span className="text-indigo-600 font-medium text-xs ml-1">
                                                                    : {target.targetTeamName} <span className="text-gray-400 font-mono text-[11px]">#{target.displayId}</span>
                                                                </span>
                                                            ) : target.displayId ? (
                                                                <span className="text-gray-400 font-mono text-xs ml-1">#{target.displayId}</span>
                                                            ) : null}
                                                        </td>
                                                        <td className="px-6 py-4 min-w-[280px]">
                                                            <div className="bg-gray-50/80 rounded-xl p-3 border border-gray-100 max-w-xl">
                                                                <LogChangesDisplay log={log} />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Pagination */}
                                {logs.links && logs.links.length > 3 && (
                                    <div className="px-6 py-4 border-t border-gray-100 flex items-center justify-between flex-wrap gap-2">
                                        <div className="text-xs text-gray-500">
                                            แสดงผล <span className="font-bold">{logs.from}</span> ถึง <span className="font-bold">{logs.to}</span> จากทั้งหมด <span className="font-bold">{logs.total}</span> รายการ
                                        </div>
                                        <div className="flex gap-1 flex-wrap">
                                            {logs.links.map((link, idx) => (
                                                <button
                                                    key={idx}
                                                    disabled={!link.url || link.active}
                                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                                    className={`px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                                                        link.active
                                                            ? 'bg-indigo-600 text-white font-bold shadow-xs'
                                                            : link.url
                                                            ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                                                            : 'bg-gray-50 text-gray-300 border border-gray-100 cursor-not-allowed'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </>
                        ) : (
                            <div className="text-center py-12 text-gray-500">
                                <svg className="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                ไม่พบประวัติการใช้งานตามเงื่อนไขที่กำหนด
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
