import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ categories = [] }) {
    const { flash } = usePage().props;

    const handleDelete = (category) => {
        if (!confirm(`ยืนยันการลบหมวดหมู่ "${category.name}"?`)) {
            return;
        }

        router.delete(route('categories.destroy', category.id));
    };

    return (
        <>
            <Head title="จัดการหมวดหมู่ปัญหา (Categories)" />

            <div className="py-6 sm:py-8 bg-gray-50 min-h-screen">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h1 className="text-2xl font-black text-gray-900 tracking-tight">
                                จัดการหมวดหมู่ปัญหา (Categories)
                            </h1>
                            <p className="text-sm text-gray-500 mt-1">
                                จัดการประเภทและหมวดหมู่สำหรับใช้จัดกลุ่มใบแจ้งซ่อมในระบบ
                            </p>
                        </div>
                        <Link
                            href={route('categories.create')}
                            className="inline-flex items-center gap-1.5 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-900 text-white text-xs font-bold uppercase tracking-wider rounded-lg shadow-sm transition"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>เพิ่มหมวดหมู่ใหม่</span>
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

                    {/* Content */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-6">
                        {categories.length > 0 ? (
                            <>
                                {/* Mobile Card View */}
                                <div className="md:hidden space-y-4">
                                    {categories.map((cat) => (
                                        <div key={cat.id} className="bg-white border border-gray-200 rounded-xl shadow-xs p-4 space-y-3">
                                            <div className="flex justify-between items-start gap-2 border-b border-gray-100 pb-2">
                                                <h4 className="text-sm font-bold text-gray-900">{cat.name}</h4>
                                                {cat.is_active ? (
                                                    <span className="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                                        เปิดใช้งาน
                                                    </span>
                                                ) : (
                                                    <span className="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                                        ปิดใช้งาน
                                                    </span>
                                                )}
                                            </div>

                                            <p className="text-xs text-gray-500">
                                                <span className="font-semibold text-gray-700">รายละเอียด: </span>
                                                {cat.description || '-'}
                                            </p>

                                            <div className="flex gap-2 pt-2 border-t border-gray-100">
                                                <Link
                                                    href={route('categories.edit', cat.id)}
                                                    className="flex-1 text-center font-bold text-xs text-indigo-700 bg-indigo-50 hover:bg-indigo-100 py-2 rounded-lg transition"
                                                >
                                                    แก้ไข
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(cat)}
                                                    className="flex-1 text-center font-bold text-xs text-rose-700 bg-rose-50 hover:bg-rose-100 py-2 rounded-lg transition"
                                                >
                                                    ลบ
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Desktop Table View */}
                                <div className="hidden md:block overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 border border-gray-100 rounded-xl overflow-hidden">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ชื่อหมวดหมู่</th>
                                                <th className="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                                                <th className="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">สถานะ</th>
                                                <th className="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-100 text-sm">
                                            {categories.map((cat) => (
                                                <tr key={cat.id} className="hover:bg-gray-50/80 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{cat.name}</td>
                                                    <td className="px-6 py-4 text-xs text-gray-500 max-w-xs truncate">{cat.description || '-'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {cat.is_active ? (
                                                            <span className="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                                เปิดใช้งาน
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                                                ปิดใช้งาน
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <Link
                                                                href={route('categories.edit', cat.id)}
                                                                className="text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition"
                                                            >
                                                                แก้ไข
                                                            </Link>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDelete(cat)}
                                                                className="text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition"
                                                            >
                                                                ลบ
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </>
                        ) : (
                            <div className="text-center py-12 text-gray-500">
                                <svg className="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                ยังไม่มีรายการหมวดหมู่ปัญหาในระบบ
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
