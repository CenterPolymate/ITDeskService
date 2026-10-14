import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Create() {
    const { errors } = usePage().props;
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [isActive, setIsActive] = useState(true);
    const [saving, setSaving] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setSaving(true);

        router.post(
            route('categories.store'),
            {
                name,
                description,
                is_active: isActive ? 1 : 0,
            },
            {
                onFinish: () => setSaving(false),
            }
        );
    };

    return (
        <>
            <Head title="เพิ่มหมวดหมู่ปัญหาใหม่" />

            <div className="py-6 sm:py-8 bg-gray-50 min-h-screen">
                <div className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div>
                        <h1 className="text-2xl font-black text-gray-900 tracking-tight">
                            เพิ่มหมวดหมู่ปัญหาใหม่
                        </h1>
                        <p className="text-sm text-gray-500 mt-1">
                            สร้างหมวดหมู่ใหม่สำหรับจัดประเภทใบงาน
                        </p>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-6 sm:p-8">
                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div>
                                <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    ชื่อหมวดหมู่ (Category Name) <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    required
                                    autoFocus
                                    placeholder="เช่น ฮาร์ดแวร์, ซอฟต์แวร์, ระบบเครือข่าย"
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm leading-normal"
                                />
                                {errors.name && <p className="mt-1 text-xs text-rose-600">{errors.name}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    รายละเอียด (Description)
                                </label>
                                <textarea
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    rows={3}
                                    placeholder="คำอธิบายเพิ่มเติมเกี่ยวกับหมวดหมู่นี้"
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm leading-relaxed"
                                />
                                {errors.description && (
                                    <p className="mt-1 text-xs text-rose-600">{errors.description}</p>
                                )}
                            </div>

                            <div className="pt-2">
                                <label className="inline-flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={isActive}
                                        onChange={(e) => setIsActive(e.target.checked)}
                                        className="rounded border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500 h-4 w-4"
                                    />
                                    <span className="text-sm font-semibold text-gray-700">เปิดใช้งานทันที (Active)</span>
                                </label>
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                                <Link
                                    href={route('categories.index')}
                                    className="px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-bold text-xs text-gray-700 uppercase tracking-wider shadow-xs hover:bg-gray-50 transition"
                                >
                                    ยกเลิก
                                </Link>
                                <button
                                    type="submit"
                                    disabled={saving}
                                    className="px-6 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-indigo-700 shadow-sm transition disabled:opacity-50"
                                >
                                    {saving ? 'กำลังบันทึก...' : 'บันทึก'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
