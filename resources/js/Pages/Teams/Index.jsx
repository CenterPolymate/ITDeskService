import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ teams = [], stats = {}, filters = {} }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route('teams.index'),
            { search, status: statusFilter },
            { preserveState: true, replace: true }
        );
    };

    const handleStatusChange = (val) => {
        setStatusFilter(val);
        router.get(
            route('teams.index'),
            { search, status: val },
            { preserveState: true, replace: true }
        );
    };

    const handleToggleStatus = (team) => {
        router.patch(route('teams.toggle_status', team.id), {}, { preserveScroll: true });
    };

    const handleDelete = (team) => {
        if (!confirm(`ยืนยันการลบทีม "${team.name}" (${team.code})? การลบนี้จะยกเลิกการสังกัดทีมของสมาชิกทุกคนในทีมนี้`)) {
            return;
        }
        router.delete(route('teams.destroy', team.id));
    };

    return (
        <>
            <Head title="จัดการทีมงานและสายการบังคับบัญชา (Team Management)" />

            <div className="py-6 sm:py-8 bg-gray-50 min-h-screen">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </span>
                                <div>
                                    <h1 className="text-2xl font-black text-gray-900 tracking-tight">
                                        จัดการทีมงานและสายการบังคับบัญชา (Teams & Hierarchy)
                                    </h1>
                                    <p className="text-sm text-gray-500 mt-0.5">
                                        กำหนดโครงสร้างทีม ระบุหัวหน้าทีม (Manager) และจัดการสมาชิกผู้ปฏิบัติงานในแต่ละทีมแบบไดนามิก
                                    </p>
                                </div>
                            </div>
                        </div>

                        <Link
                            href={route('teams.create')}
                            className="inline-flex items-center gap-1.5 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-900 text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-sm transition hover:shadow cursor-pointer"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>สร้างทีมใหม่</span>
                        </Link>
                    </div>

                    {/* Flash messages */}
                    {flash?.success && (
                        <div className="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-xs flex items-center gap-2">
                            <svg className="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span className="text-sm font-semibold">{flash.success}</span>
                        </div>
                    )}

                    {flash?.error && (
                        <div className="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-xs flex items-center gap-2">
                            <svg className="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span className="text-sm font-semibold">{flash.error}</span>
                        </div>
                    )}

                    {/* KPI Cards */}
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-xs">
                            <span className="text-xs font-semibold text-gray-500">ทีมทั้งหมด</span>
                            <div className="mt-1 flex items-baseline gap-2">
                                <span className="text-2xl font-black text-gray-900">{stats.total_teams || 0}</span>
                                <span className="text-xs text-gray-500">ทีม</span>
                            </div>
                        </div>

                        <div className="bg-white p-4 rounded-2xl border border-emerald-200/80 shadow-xs">
                            <span className="text-xs font-semibold text-emerald-600">เปิดใช้งานอยู่</span>
                            <div className="mt-1 flex items-baseline gap-2">
                                <span className="text-2xl font-black text-emerald-700">{stats.active_teams || 0}</span>
                                <span className="text-xs text-emerald-600">ทีม</span>
                            </div>
                        </div>

                        <div className="bg-white p-4 rounded-2xl border border-blue-200/80 shadow-xs">
                            <span className="text-xs font-semibold text-blue-600">สมาชิกในทีมทั้งหมด</span>
                            <div className="mt-1 flex items-baseline gap-2">
                                <span className="text-2xl font-black text-blue-700">{stats.total_members || 0}</span>
                                <span className="text-xs text-blue-600">คน</span>
                            </div>
                        </div>

                        <div className="bg-white p-4 rounded-2xl border border-purple-200/80 shadow-xs">
                            <span className="text-xs font-semibold text-purple-600">หัวหน้าทีม (Manager)</span>
                            <div className="mt-1 flex items-baseline gap-2">
                                <span className="text-2xl font-black text-purple-700">{stats.total_managers || 0}</span>
                                <span className="text-xs text-purple-600">คน</span>
                            </div>
                        </div>
                    </div>

                    {/* Search & Filter Bar */}
                    <div className="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-xs flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                        <form onSubmit={handleSearch} className="flex-1 flex gap-2">
                            <div className="relative flex-1">
                                <svg className="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="ค้นหาชื่อทีม, รหัสทีม, คำอธิบาย..."
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                />
                            </div>
                            <button
                                type="submit"
                                className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition cursor-pointer"
                            >
                                ค้นหา
                            </button>
                        </form>

                        <div className="flex items-center gap-2 shrink-0">
                            <span className="text-xs text-gray-500 font-medium">สถานะ:</span>
                            <div className="inline-flex rounded-xl bg-gray-100 p-1 border border-gray-200">
                                <button
                                    type="button"
                                    onClick={() => handleStatusChange('all')}
                                    className={`px-3 py-1 rounded-lg text-xs font-semibold transition cursor-pointer ${
                                        statusFilter === 'all'
                                            ? 'bg-white text-gray-900 shadow-xs'
                                            : 'text-gray-500 hover:text-gray-700'
                                    }`}
                                >
                                    ทั้งหมด
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleStatusChange('active')}
                                    className={`px-3 py-1 rounded-lg text-xs font-semibold transition cursor-pointer ${
                                        statusFilter === 'active'
                                            ? 'bg-white text-emerald-700 shadow-xs'
                                            : 'text-gray-500 hover:text-gray-700'
                                    }`}
                                >
                                    เปิดใช้งาน
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleStatusChange('inactive')}
                                    className={`px-3 py-1 rounded-lg text-xs font-semibold transition cursor-pointer ${
                                        statusFilter === 'inactive'
                                            ? 'bg-white text-rose-700 shadow-xs'
                                            : 'text-gray-500 hover:text-gray-700'
                                    }`}
                                >
                                    ระงับ
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Teams List (Desktop & Mobile) */}
                    <div className="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                        {teams.length === 0 ? (
                            <div className="p-12 text-center space-y-3">
                                <div className="w-12 h-12 mx-auto rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h3 className="text-base font-bold text-gray-900">ไม่พบข้อมูลทีม</h3>
                                <p className="text-sm text-gray-500 max-w-sm mx-auto">
                                    {search || statusFilter !== 'all'
                                        ? 'ไม่พบทีมที่ตรงกับเงื่อนไขการค้นหา ลองปรับตัวกรองใหม่'
                                        : 'ยังไม่มีทีมในระบบ เริ่มต้นสร้างทีมแรกเพื่อจัดกลุ่มสมาชิกและกำหนดหัวหน้างาน'}
                                </p>
                                <Link
                                    href={route('teams.create')}
                                    className="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition shadow-xs"
                                >
                                    + สร้างทีมแรก
                                </Link>
                            </div>
                        ) : (
                            <>
                                {/* Mobile Cards View (md:hidden) */}
                                <div className="md:hidden divide-y divide-gray-100">
                                    {teams.map((team) => (
                                        <div key={team.id} className="p-4 space-y-3">
                                            <div className="flex justify-between items-start gap-2">
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-xs font-bold px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md border border-indigo-200">
                                                            {team.code}
                                                        </span>
                                                        <h3 className="text-base font-bold text-gray-900">{team.name}</h3>
                                                    </div>
                                                    {team.description && (
                                                        <p className="text-xs text-gray-500 mt-1 line-clamp-2">{team.description}</p>
                                                    )}
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => handleToggleStatus(team)}
                                                    className={`px-2.5 py-1 text-[11px] font-bold rounded-full transition cursor-pointer ${
                                                        team.is_active
                                                            ? 'bg-emerald-100 text-emerald-800'
                                                            : 'bg-rose-100 text-rose-800'
                                                    }`}
                                                >
                                                    {team.is_active ? 'เปิดใช้งาน' : 'ระงับ'}
                                                </button>
                                            </div>

                                            {/* Manager info */}
                                            <div className="bg-gray-50 p-2.5 rounded-xl border border-gray-100 space-y-1">
                                                <span className="text-[11px] font-semibold text-gray-500">หัวหน้าทีม (Supervisor):</span>
                                                {team.manager ? (
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-6 h-6 rounded-full bg-purple-100 text-purple-700 font-bold text-[10px] flex items-center justify-center">
                                                            {team.manager.name.charAt(0)}
                                                        </div>
                                                        <span className="text-xs font-bold text-gray-900">{team.manager.name}</span>
                                                        <span className="text-[10px] text-gray-400">({team.manager.email})</span>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-amber-600 font-medium block">⚠️ ยังไม่ระบุหัวหน้าทีม</span>
                                                )}
                                            </div>

                                            {/* Members summary */}
                                            <div className="flex items-center justify-between text-xs text-gray-600 pt-1">
                                                <span>สมาชิกในทีม: <strong>{team.members?.length || 0} คน</strong></span>
                                                <div className="flex items-center gap-2">
                                                    <Link
                                                        href={route('teams.edit', team.id)}
                                                        className="text-indigo-600 font-bold hover:underline"
                                                    >
                                                        แก้ไข
                                                    </Link>
                                                    <span className="text-gray-300">|</span>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(team)}
                                                        className="text-rose-600 font-bold hover:underline cursor-pointer"
                                                    >
                                                        ลบ
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Desktop Table View (hidden md:block) */}
                                <div className="hidden md:block overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50/80">
                                            <tr>
                                                <th scope="col" className="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                    ทีม / รหัสทีม
                                                </th>
                                                <th scope="col" className="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                    หัวหน้าทีม (Manager)
                                                </th>
                                                <th scope="col" className="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                    สมาชิกในทีม (Team Members)
                                                </th>
                                                <th scope="col" className="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                    สถานะ
                                                </th>
                                                <th scope="col" className="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                    จัดการ
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-100">
                                            {teams.map((team) => (
                                                <tr key={team.id} className="hover:bg-slate-50 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center gap-2.5">
                                                            <span className="font-mono text-xs font-bold px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md border border-indigo-200 shrink-0">
                                                                {team.code}
                                                            </span>
                                                            <div>
                                                                <div className="text-sm font-bold text-gray-900">{team.name}</div>
                                                                {team.description && (
                                                                    <div className="text-xs text-gray-500 max-w-xs truncate">{team.description}</div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {team.manager ? (
                                                            <div className="flex items-center gap-2">
                                                                <div className="w-7 h-7 rounded-full bg-purple-100 text-purple-700 font-bold text-xs flex items-center justify-center shrink-0">
                                                                    {team.manager.name.charAt(0)}
                                                                </div>
                                                                <div>
                                                                    <div className="text-sm font-bold text-gray-900 leading-snug">{team.manager.name}</div>
                                                                    <div className="text-xs text-gray-400">{team.manager.email}</div>
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <span className="inline-flex items-center gap-1 text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md font-medium border border-amber-200">
                                                                ⚠️ ยังไม่ระบุหัวหน้าทีม
                                                            </span>
                                                        )}
                                                    </td>

                                                    <td className="px-6 py-4">
                                                        {team.members && team.members.length > 0 ? (
                                                            <div className="flex items-center gap-1.5 flex-wrap max-w-md">
                                                                <span className="text-xs font-bold text-gray-700 bg-gray-100 px-2 py-0.5 rounded-full mr-1">
                                                                    {team.members.length} คน
                                                                </span>
                                                                {team.members.slice(0, 3).map((m) => (
                                                                    <span
                                                                        key={m.id}
                                                                        className="inline-flex items-center gap-1 text-xs text-slate-700 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded-lg"
                                                                    >
                                                                        {m.name}
                                                                    </span>
                                                                ))}
                                                                {team.members.length > 3 && (
                                                                    <span className="text-xs text-gray-400 font-semibold">
                                                                        +{team.members.length - 3}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <span className="text-xs text-gray-400">ยังไม่มีสมาชิกในทีม</span>
                                                        )}
                                                    </td>

                                                    <td className="px-6 py-4 whitespace-nowrap text-center">
                                                        <button
                                                            type="button"
                                                            onClick={() => handleToggleStatus(team)}
                                                            className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold transition cursor-pointer ${
                                                                team.is_active
                                                                    ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200'
                                                                    : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200'
                                                            }`}
                                                            title="คลิกเพื่อสลับสถานะ"
                                                        >
                                                            <span className={`w-1.5 h-1.5 rounded-full ${team.is_active ? 'bg-emerald-500' : 'bg-rose-500'}`} />
                                                            <span>{team.is_active ? 'เปิดใช้งาน' : 'ระงับ'}</span>
                                                        </button>
                                                    </td>

                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <Link
                                                                href={route('teams.edit', team.id)}
                                                                className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition cursor-pointer"
                                                            >
                                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                                <span>แก้ไข</span>
                                                            </Link>

                                                            <button
                                                                type="button"
                                                                onClick={() => handleDelete(team)}
                                                                className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 transition cursor-pointer"
                                                            >
                                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                                <span>ลบ</span>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
