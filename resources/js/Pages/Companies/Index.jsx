import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ companies }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState('');
    const [showImportModal, setShowImportModal] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importing, setImporting] = useState(false);
    const [importError, setImportError] = useState('');

    const filteredCompanies = companies.filter((c) => {
        if (!search.trim()) return true;
        const q = search.toLowerCase();
        return (
            c.name?.toLowerCase().includes(q) ||
            c.short_name?.toLowerCase().includes(q) ||
            c.email_domains?.toLowerCase().includes(q)
        );
    });

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

        router.post(route('companies.import'), formData, {
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

    const handleToggleStatus = (company) => {
        const actionText = company.is_active ? 'ระงับการใช้งาน' : 'เปิดใช้งาน';
        if (confirm(`ยืนยันการ${actionText}บริษัท "${company.name}"?`)) {
            router.patch(route('companies.toggle_status', company.id));
        }
    };

    const handleDelete = (company) => {
        if (confirm(`ยืนยันการลบบริษัท "${company.name}"?\n(หากไม่มีข้อมูลเชื่อมโยงระบบจะลบถาวร หรือระงับการใช้งานหากมีข้อมูลแล้ว)`)) {
            router.delete(route('companies.destroy', company.id));
        }
    };

    return (
        <>
            <Head title="จัดการรายชื่อบริษัท (Companies)" />

            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            จัดการรายชื่อบริษัท (Companies)
                        </h2>
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
                                href={route('companies.export')}
                                className="inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition shadow-sm w-full sm:w-auto"
                            >
                                <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 013 3h10a3 3 0 013-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Export CSV
                            </a>
                            <Link
                                href={route('companies.create')}
                                className="inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition shadow-sm w-full sm:w-auto"
                            >
                                + เพิ่มบริษัทใหม่
                            </Link>
                        </div>
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
                            {/* Search bar */}
                            <div className="mb-4">
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="ค้นหาบริษัท, โดเมน..."
                                    className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-1/3 text-sm leading-normal"
                                />
                            </div>

                            {/* Mobile Card View */}
                            <div className="md:hidden space-y-4 mb-4">
                                {filteredCompanies.length > 0 ? (
                                    filteredCompanies.map((company) => (
                                        <div
                                            key={company.id}
                                            className={`bg-white border rounded-xl shadow-sm p-4 ${
                                                company.is_active ? 'border-gray-200' : 'border-red-200 bg-red-50/30'
                                            }`}
                                        >
                                            <div className="flex justify-between items-start mb-2">
                                                <h4 className={`font-bold ${company.is_active ? 'text-gray-900' : 'text-gray-500 line-through'}`}>
                                                    {company.name}
                                                </h4>
                                                {company.is_active ? (
                                                    <span className="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full bg-green-100 text-green-800 shadow-sm whitespace-nowrap">
                                                        เปิดใช้งาน
                                                    </span>
                                                ) : (
                                                    <span className="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full bg-red-100 text-red-800 shadow-sm whitespace-nowrap">
                                                        ปิดใช้งาน
                                                    </span>
                                                )}
                                            </div>

                                            <div className="text-sm text-gray-600 mb-3 space-y-2">
                                                {company.short_name && (
                                                    <div className="flex items-start gap-2">
                                                        <span className="text-gray-400 font-medium w-20 shrink-0">ชื่อย่อ:</span>
                                                        <span>{company.short_name}</span>
                                                    </div>
                                                )}
                                                <div className="flex items-start gap-2">
                                                    <span className="text-gray-400 font-medium w-20 shrink-0 mt-0.5">อีเมลโดเมน:</span>
                                                    <div className="flex flex-wrap gap-1">
                                                        {company.email_domains ? (
                                                            company.email_domains.split(',').map((domain, idx) => (
                                                                <span
                                                                    key={idx}
                                                                    className={`inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium ${
                                                                        company.is_active ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-500'
                                                                    }`}
                                                                >
                                                                    {domain.trim()}
                                                                </span>
                                                            ))
                                                        ) : (
                                                            <span className="text-gray-400 italic text-xs mt-0.5">ไม่ได้จำกัด</span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
                                                <Link
                                                    href={route('companies.edit', company.id)}
                                                    className="flex-1 text-center text-xs text-indigo-700 font-medium hover:text-indigo-900 transition-colors px-2 py-2 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-100"
                                                >
                                                    แก้ไข
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => handleToggleStatus(company)}
                                                    className={`flex-1 text-center text-xs font-medium transition-colors px-2 py-2 rounded-lg border ${
                                                        company.is_active
                                                            ? 'text-amber-700 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 border-amber-200'
                                                            : 'text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 border-emerald-200'
                                                    }`}
                                                >
                                                    {company.is_active ? 'ระงับ' : 'เปิดใช้งาน'}
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(company)}
                                                    className="flex-1 text-center text-xs text-red-700 font-medium hover:text-red-900 transition-colors px-2 py-2 bg-red-50 hover:bg-red-100 rounded-lg border border-red-100"
                                                >
                                                    ลบ
                                                </button>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center py-6 text-gray-500 text-sm">ไม่พบข้อมูลบริษัท</div>
                                )}
                            </div>

                            {/* Desktop Table View */}
                            <div className="hidden md:block overflow-x-auto rounded-lg border border-gray-200">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อบริษัท</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อย่อ (ถ้ามี)</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">โดเมน (Email Domains)</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                            <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {filteredCompanies.length > 0 ? (
                                            filteredCompanies.map((company) => (
                                                <tr key={company.id} className={company.is_active ? '' : 'bg-red-50/50'}>
                                                    <td className={`px-4 py-2 whitespace-nowrap font-medium ${company.is_active ? 'text-gray-900' : 'text-gray-400 line-through'}`}>
                                                        {company.name}
                                                    </td>
                                                    <td className={`px-4 py-2 whitespace-nowrap text-sm ${company.is_active ? 'text-gray-500' : 'text-gray-400'}`}>
                                                        {company.short_name || '-'}
                                                    </td>
                                                    <td className={`px-4 py-2 text-sm ${company.is_active ? 'text-gray-500' : 'text-gray-400'}`}>
                                                        {company.email_domains ? (
                                                            company.email_domains.split(',').map((domain, idx) => (
                                                                <span
                                                                    key={idx}
                                                                    className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                                                                        company.is_active ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-500'
                                                                    } mr-1 mb-1`}
                                                                >
                                                                    {domain.trim()}
                                                                </span>
                                                            ))
                                                        ) : (
                                                            <span className="text-gray-400 italic">ไม่ได้จำกัด</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-2 whitespace-nowrap">
                                                        {company.is_active ? (
                                                            <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                                เปิดใช้งาน
                                                            </span>
                                                        ) : (
                                                            <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                                ปิดใช้งาน
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-2 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <Link
                                                                href={route('companies.edit', company.id)}
                                                                className="text-indigo-600 hover:text-indigo-900 transition-colors px-2 py-1 bg-indigo-50 hover:bg-indigo-100 rounded-md border border-indigo-100"
                                                            >
                                                                แก้ไข
                                                            </Link>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleToggleStatus(company)}
                                                                className={`transition-colors px-2 py-1 rounded-md border ${
                                                                    company.is_active
                                                                        ? 'text-amber-700 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 border-amber-200'
                                                                        : 'text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 border-emerald-200'
                                                                }`}
                                                            >
                                                                {company.is_active ? 'ระงับ' : 'เปิดใช้งาน'}
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDelete(company)}
                                                                className="text-red-600 hover:text-red-900 transition-colors px-2 py-1 bg-red-50 hover:bg-red-100 rounded-md border border-red-100"
                                                            >
                                                                ลบ
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="5" className="px-4 py-6 text-center text-gray-500 text-sm">
                                                    ไม่พบข้อมูลบริษัท
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Import CSV Modal */}
            {showImportModal && (
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
                                        นำเข้าบริษัทจากไฟล์ CSV
                                    </h3>
                                    <div className="mt-2">
                                        <p className="text-sm text-gray-500 mb-4">
                                            ไฟล์ CSV ต้องมีคอลัมน์ตามลำดับดังนี้: <br />
                                            <code className="text-xs bg-gray-100 px-1 py-0.5 rounded">
                                                ชื่อบริษัท, ชื่อย่อ, อีเมลโดเมน
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
