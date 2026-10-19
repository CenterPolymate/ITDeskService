import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ users, filters = {} }) {
    const { flash, auth } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status_filter || '');
    const [showImportModal, setShowImportModal] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importing, setImporting] = useState(false);
    const [importError, setImportError] = useState('');

    const formatThaiDate = (dateStr) => {
        if (!dateStr) return 'ไม่เคยเข้าใช้งาน';
        const d = new Date(dateStr);
        const months = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        router.get(
            route('normal_users.index'),
            {
                search: search || undefined,
                status_filter: statusFilter !== '' ? statusFilter : undefined,
                sort: filters.sort || undefined,
                direction: filters.direction || undefined,
            },
            { preserveState: true }
        );
    };

    const handleSort = (field) => {
        const currentSort = filters.sort || 'created_at';
        const currentDirection = filters.direction || 'desc';
        const newDirection = currentSort === field && currentDirection === 'asc' ? 'desc' : 'asc';

        router.get(
            route('normal_users.index'),
            {
                ...filters,
                sort: field,
                direction: newDirection,
            },
            { preserveState: true }
        );
    };

    const handleImportSubmit = (e) => {
        e.preventDefault();
        if (!importFile) {
            setImportError('กรุณาเลือกไฟล์ CSV');
            return;
        }

        setImporting(true);
        setImportError('');

        const formData = new FormData();
        formData.append('csv_file', importFile);

        router.post(route('normal_users.import'), formData, {
            onSuccess: () => {
                setShowImportModal(false);
                setImportFile(null);
            },
            onError: (errors) => {
                setImportError(errors.csv_file || 'เกิดข้อผิดพลาดในการนำเข้าไฟล์');
            },
            onFinish: () => setImporting(false),
        });
    };

    const handleDelete = (user) => {
        if (confirm(`ยืนยันการทำรายการกับ "${user.name}"?\n(ระบบจะลบถาวรหากไม่มีประวัติ หรือระงับบัญชีหากมีประวัติแล้ว)`)) {
            router.delete(route('normal_users.destroy', user.id));
        }
    };

    const handleImpersonate = (userId) => {
        router.post(route('impersonate', userId));
    };

    const renderSortIcon = (field) => {
        if (filters.sort !== field) {
            return (
                <svg className="w-3 h-3 text-gray-400 opacity-60 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
            );
        }
        return filters.direction === 'asc' ? (
            <svg className="w-3 h-3 text-indigo-600 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 15l7-7 7 7" />
            </svg>
        ) : (
            <svg className="w-3 h-3 text-indigo-600 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
            </svg>
        );
    };

    return (
        <>
            <Head title="จัดการผู้ใช้งานทั่วไป (Normal Users)" />

            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            จัดการผู้ใช้งานทั่วไป
                        </h2>
                        {auth.user?.role === 'administrator' && (
                            <div className="flex flex-wrap gap-2 w-full sm:w-auto">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setImportError('');
                                        setShowImportModal(true);
                                    }}
                                    className="inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 transition shadow-sm w-full sm:w-auto"
                                >
                                    <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Import CSV
                                </button>
                                <a
                                    href={route('normal_users.export', filters)}
                                    className="inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition shadow-sm w-full sm:w-auto"
                                >
                                    <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 013 3h10a3 3 0 013-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Export CSV
                                </a>
                                <Link
                                    href={route('normal_users.create')}
                                    className="inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition shadow-sm w-full sm:w-auto"
                                >
                                    + เพิ่มผู้ใช้งานใหม่
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </header>

            <div className="py-4 sm:py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
                    {flash?.success && (
                        <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative flex items-center justify-between">
                            <span>{flash.success}</span>
                        </div>
                    )}
                    {flash?.error && (
                        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative flex items-center justify-between">
                            <span>{flash.error}</span>
                        </div>
                    )}

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {/* Search and Filters */}
                            <form onSubmit={handleSearchSubmit} className="mb-4 flex flex-col md:flex-row gap-2">
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="ค้นหาชื่อ หรืออีเมล..."
                                    className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-1/3 text-sm leading-normal"
                                />
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-auto text-sm leading-normal"
                                >
                                    <option value="">สถานะทั้งหมด</option>
                                    <option value="1">ใช้งาน (Active)</option>
                                    <option value="unverified">รอการยืนยันอีเมล</option>
                                    <option value="0">ระงับการใช้งาน (Inactive)</option>
                                </select>
                                <button
                                    type="submit"
                                    className="inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition w-full md:w-auto"
                                >
                                    ค้นหา
                                </button>
                            </form>

                            {/* Mobile Card View */}
                            <div className="md:hidden space-y-4 mb-4">
                                {users.data && users.data.length > 0 ? (
                                    users.data.map((user) => (
                                        <div
                                            key={user.id}
                                            className={`bg-white border rounded-xl shadow-sm p-4 ${
                                                user.is_active ? 'border-gray-200' : 'border-red-200 bg-red-50/30'
                                            }`}
                                        >
                                            <div className="flex justify-between items-start mb-2">
                                                <h4 className={`font-bold ${user.is_active ? 'text-gray-900' : 'text-gray-500 line-through'}`}>
                                                    {user.name}
                                                </h4>
                                                {!user.is_active ? (
                                                    <span className="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full bg-red-100 text-red-800 shadow-sm">
                                                        Inactive
                                                    </span>
                                                ) : !user.email_verified_at ? (
                                                    <span className="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full bg-amber-100 text-amber-800 shadow-sm">
                                                        รอการยืนยันอีเมล
                                                    </span>
                                                ) : (
                                                    <span className="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full bg-green-100 text-green-800 shadow-sm">
                                                        Active
                                                    </span>
                                                )}
                                            </div>

                                            <div className="text-sm text-gray-600 mb-3 space-y-1">
                                                <div className="flex items-start gap-2">
                                                    <svg className="w-4 h-4 mt-0.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                    <span className="break-all">{user.email}</span>
                                                </div>
                                                <div className="flex items-start gap-2">
                                                    <svg className="w-4 h-4 mt-0.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>{user.company} <span className="text-gray-400">/</span> {user.department || '-'}</span>
                                                </div>
                                                <div className="flex items-start gap-2">
                                                    <svg className="w-4 h-4 mt-0.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span className="text-[11px] mt-0.5">
                                                        {user.last_login_at ? `เข้าใช้งาน: ${formatThaiDate(user.last_login_at)}` : 'ไม่เคยเข้าใช้งาน'}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
                                                {auth.user?.role === 'administrator' && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleImpersonate(user.id)}
                                                        className="flex-1 text-center text-xs text-emerald-700 font-medium hover:text-emerald-900 transition-colors px-2 py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100"
                                                    >
                                                        Login As
                                                    </button>
                                                )}
                                                <Link
                                                    href={route('normal_users.edit', user.id)}
                                                    className="flex-1 text-center text-xs text-indigo-700 font-medium hover:text-indigo-900 transition-colors px-2 py-2 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-100"
                                                >
                                                    แก้ไข
                                                </Link>
                                                {auth.user?.id !== user.id && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(user)}
                                                        className="flex-1 text-center text-xs text-red-700 font-medium hover:text-red-900 transition-colors px-2 py-2 bg-red-50 hover:bg-red-100 rounded-lg border border-red-100"
                                                    >
                                                        ลบ
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center py-6 text-gray-500 text-sm">ไม่พบข้อมูลผู้ใช้งาน</div>
                                )}
                            </div>

                            {/* Desktop Table View */}
                            <div className="hidden md:block overflow-x-auto rounded-lg border border-gray-200">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th
                                                onClick={() => handleSort('name')}
                                                className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            >
                                                ผู้ใช้งาน {renderSortIcon('name')}
                                            </th>
                                            <th
                                                onClick={() => handleSort('company')}
                                                className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            >
                                                สังกัด {renderSortIcon('company')}
                                            </th>
                                            <th
                                                onClick={() => handleSort('is_active')}
                                                className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            >
                                                สถานะ {renderSortIcon('is_active')}
                                            </th>
                                            <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                จัดการ
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {users.data && users.data.length > 0 ? (
                                            users.data.map((user) => (
                                                <tr key={user.id} className={user.is_active ? '' : 'bg-red-50/50'}>
                                                    <td className="px-4 py-2.5 whitespace-nowrap">
                                                        <div className="flex items-center gap-3">
                                                            <div className="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-sm shrink-0">
                                                                {user.name ? user.name.charAt(0).toUpperCase() : '?'}
                                                            </div>
                                                            <div>
                                                                <div className={`text-sm font-semibold ${user.is_active ? 'text-gray-900' : 'text-gray-400 line-through'}`}>
                                                                    {user.name}
                                                                </div>
                                                                <div className="text-xs text-gray-500">
                                                                    {user.email}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-2.5 whitespace-nowrap">
                                                        <div className={`text-sm font-medium ${user.is_active ? 'text-gray-900' : 'text-gray-400'}`}>
                                                            {user.company}
                                                        </div>
                                                        <div className={`text-xs ${user.is_active ? 'text-gray-500' : 'text-gray-400'}`}>
                                                            {user.department || '-'}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-2.5 whitespace-nowrap">
                                                        {!user.is_active ? (
                                                            <span className="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 shadow-sm">
                                                                Inactive
                                                            </span>
                                                        ) : !user.email_verified_at ? (
                                                            <span className="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800 shadow-sm">
                                                                รอการยืนยันอีเมล
                                                            </span>
                                                        ) : (
                                                            <span className="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 shadow-sm">
                                                                Active
                                                            </span>
                                                        )}
                                                        <div className="text-[11px] text-gray-400 mt-1 whitespace-nowrap">
                                                            {user.last_login_at ? (
                                                                <>เข้าใช้งาน: {formatThaiDate(user.last_login_at)}</>
                                                            ) : (
                                                                'ไม่เคยเข้าใช้งาน'
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-2 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex items-center justify-end gap-3">
                                                            {auth.user?.role === 'administrator' && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleImpersonate(user.id)}
                                                                    className="text-emerald-600 hover:text-emerald-900 transition-colors px-2 py-1 bg-emerald-50 hover:bg-emerald-100 rounded-md"
                                                                >
                                                                    Login As
                                                                </button>
                                                            )}
                                                            <Link
                                                                href={route('normal_users.edit', user.id)}
                                                                className="text-indigo-600 hover:text-indigo-900 transition-colors px-2 py-1 bg-indigo-50 hover:bg-indigo-100 rounded-md"
                                                            >
                                                                แก้ไข
                                                            </Link>
                                                            {auth.user?.id !== user.id && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleDelete(user)}
                                                                    className="text-red-600 hover:text-red-900 transition-colors px-2 py-1 bg-red-50 hover:bg-red-100 rounded-md"
                                                                >
                                                                    ลบ
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="4" className="px-4 py-6 text-center text-gray-500 text-sm">
                                                    ไม่พบข้อมูลผู้ใช้งาน
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {users.links && users.links.length > 3 && (
                                <div className="mt-4 flex flex-wrap justify-between items-center gap-2">
                                    <div className="text-sm text-gray-500">
                                        แสดง {users.from || 0} ถึง {users.to || 0} จากทั้งหมด {users.total || 0} รายการ
                                    </div>
                                    <div className="flex flex-wrap gap-1">
                                        {users.links.map((link, idx) => (
                                            <button
                                                key={idx}
                                                disabled={!link.url || link.active}
                                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`px-3 py-1 text-xs rounded border transition ${
                                                    link.active
                                                        ? 'bg-indigo-600 text-white border-indigo-600 font-bold'
                                                        : link.url
                                                        ? 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'
                                                        : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'
                                                }`}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Import CSV Modal */}
            {auth.user?.role === 'administrator' && showImportModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div
                            className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                            aria-hidden="true"
                            onClick={() => setShowImportModal(false)}
                        ></div>
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form onSubmit={handleImportSubmit}>
                                <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        นำเข้าผู้ใช้งานจากไฟล์ CSV
                                    </h3>
                                    <div className="mt-2">
                                        <p className="text-sm text-gray-500 mb-4">
                                            ไฟล์ CSV ต้องมีคอลัมน์ตามลำดับดังนี้: <br />
                                            <code className="text-xs bg-gray-100 px-1 py-0.5 rounded">
                                                ชื่อ, อีเมล, บริษัท, แผนก, เบอร์โทรศัพท์
                                            </code>
                                        </p>
                                        <input
                                            type="file"
                                            accept=".csv"
                                            required
                                            onChange={(e) => setImportFile(e.target.files[0] || null)}
                                            className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                        />
                                        {importError && (
                                            <p className="mt-2 text-sm text-red-600 font-medium">{importError}</p>
                                        )}
                                    </div>
                                </div>
                                <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                                    <button
                                        type="submit"
                                        disabled={importing}
                                        className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm disabled:opacity-50"
                                    >
                                        {importing ? 'กำลังนำเข้า...' : 'นำเข้าข้อมูล'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowImportModal(false)}
                                        className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                                    >
                                        ยกเลิก
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
