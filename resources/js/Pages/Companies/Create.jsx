import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        short_name: '',
        email_domains: '',
        sla_type: '8x5x4',
        is_active: true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('companies.store'));
    };

    return (
        <>
            <Head title="เพิ่มบริษัทใหม่" />

            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        เพิ่มบริษัทใหม่
                    </h2>
                </div>
            </header>

            <div className="py-4 sm:py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <form onSubmit={handleSubmit}>
                                <div className="mb-4">
                                    <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                        ชื่อบริษัท <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="name"
                                        required
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                                </div>

                                <div className="mb-4">
                                    <label htmlFor="short_name" className="block text-sm font-medium text-gray-700">
                                        ชื่อย่อ (ถ้ามี)
                                    </label>
                                    <input
                                        type="text"
                                        id="short_name"
                                        value={data.short_name}
                                        onChange={(e) => setData('short_name', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.short_name && <p className="mt-1 text-sm text-red-600">{errors.short_name}</p>}
                                </div>

                                <div className="mb-4">
                                    <label htmlFor="email_domains" className="block text-sm font-medium text-gray-700">
                                        โดเมนอีเมลที่อนุญาต (คั่นด้วยลูกน้ำ) <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="email_domains"
                                        required
                                        placeholder="เช่น @polymate.co.th, @gmail.com"
                                        value={data.email_domains}
                                        onChange={(e) => setData('email_domains', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.email_domains && <p className="mt-1 text-sm text-red-600">{errors.email_domains}</p>}
                                </div>

                                <div className="mb-4">
                                    <label htmlFor="sla_type" className="block text-sm font-medium text-gray-700">
                                        ประเภท SLA (SLA Type) <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="sla_type"
                                        required
                                        value={data.sla_type}
                                        onChange={(e) => setData('sla_type', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="8x5x4">8x5x4 (จันทร์-ศุกร์ 08:00-17:00 / ภายใน 4 ชม.)</option>
                                        <option value="24x7x4">24x7x4 (ตลอด 24 ชั่วโมง / ภายใน 4 ชม.)</option>
                                    </select>
                                    {errors.sla_type && <p className="mt-1 text-sm text-red-600">{errors.sla_type}</p>}
                                </div>

                                <div className="mb-6">
                                    <label className="inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.is_active}
                                            onChange={(e) => setData('is_active', e.target.checked)}
                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        />
                                        <span className="ml-2 text-sm text-gray-600">เปิดใช้งานทันที</span>
                                    </label>
                                </div>

                                <div className="flex items-center justify-end mt-8 gap-3">
                                    <Link
                                        href={route('companies.index')}
                                        className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                                    >
                                        ยกเลิก
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition shadow-sm disabled:opacity-50"
                                    >
                                        บันทึกข้อมูล
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
