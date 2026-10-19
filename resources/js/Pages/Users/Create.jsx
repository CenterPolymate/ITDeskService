import React, { useMemo } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Create({ companies = [] }) {
    const { flash, auth } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'helpdesk',
        company: '',
        department: '',
        phone: '',
    });

    const departments = useMemo(() => {
        if (!data.company) return [];
        const comp = companies.find((c) => c.name === data.company);
        return comp && comp.departments ? comp.departments : [];
    }, [data.company, companies]);

    const handleCompanyChange = (e) => {
        const val = e.target.value;
        setData((prev) => ({
            ...prev,
            company: val,
            department: '',
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('users.store'));
    };

    return (
        <>
            <Head title="เพิ่มผู้ใช้งานทีม IT" />

            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        เพิ่มผู้ใช้งานทีม IT
                    </h2>
                </div>
            </header>

            <div className="py-4 sm:py-6">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    {flash?.error && (
                        <div className="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            <span>{flash.error}</span>
                        </div>
                    )}

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <form onSubmit={handleSubmit} className="space-y-4">
                                {/* Name */}
                                <div>
                                    <label htmlFor="name" className="block font-medium text-sm text-gray-700">
                                        ชื่อ-นามสกุล <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                        required
                                        autoFocus
                                    />
                                    {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                                </div>

                                {/* Email */}
                                <div>
                                    <label htmlFor="email" className="block font-medium text-sm text-gray-700">
                                        อีเมล <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                        required
                                    />
                                    {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                                </div>

                                {/* Password */}
                                <div>
                                    <label htmlFor="password" className="block font-medium text-sm text-gray-700">
                                        รหัสผ่าน <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        autoComplete="new-password"
                                        className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                        required
                                    />
                                    {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                                </div>

                                {/* Confirm Password */}
                                <div>
                                    <label htmlFor="password_confirmation" className="block font-medium text-sm text-gray-700">
                                        ยืนยันรหัสผ่าน <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        autoComplete="new-password"
                                        className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                        required
                                    />
                                    {errors.password_confirmation && (
                                        <p className="mt-1 text-sm text-red-600">{errors.password_confirmation}</p>
                                    )}
                                </div>

                                {/* Role */}
                                <div>
                                    <label htmlFor="role" className="block font-medium text-sm text-gray-700">
                                        ตำแหน่ง (Role) <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="role"
                                        value={data.role}
                                        onChange={(e) => setData('role', e.target.value)}
                                        className="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="helpdesk">Service Desk Agent (Tier 1)</option>
                                        <option value="technician">Technical Specialist (Tier 2)</option>
                                        <option value="manager">IT Service Manager (Tier 3)</option>
                                        {auth.user?.role === 'administrator' && (
                                            <option value="administrator">System Administrator</option>
                                        )}
                                    </select>
                                    {errors.role && <p className="mt-1 text-sm text-red-600">{errors.role}</p>}
                                </div>

                                {/* Company */}
                                <div>
                                    <label htmlFor="company" className="block font-medium text-sm text-gray-700">
                                        บริษัท <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="company"
                                        value={data.company}
                                        onChange={handleCompanyChange}
                                        className="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="">เลือกบริษัท</option>
                                        {companies.map((comp) => (
                                            <option key={comp.id} value={comp.name}>
                                                {comp.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.company && <p className="mt-1 text-sm text-red-600">{errors.company}</p>}
                                </div>

                                {/* Department */}
                                <div>
                                    <label htmlFor="department" className="block font-medium text-sm text-gray-700">
                                        หน่วยงาน/แผนก (Department)
                                    </label>
                                    <select
                                        id="department"
                                        value={data.department}
                                        onChange={(e) => setData('department', e.target.value)}
                                        disabled={!data.company || departments.length === 0}
                                        className="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                    >
                                        <option value="">เลือกแผนก</option>
                                        {departments.map((dept) => (
                                            <option key={dept.id} value={dept.name}>
                                                {dept.name}
                                            </option>
                                        ))}
                                    </select>
                                    {data.company && departments.length === 0 && (
                                        <p className="mt-1 text-sm text-gray-500">บริษัทนี้ยังไม่มีการตั้งค่าแผนก</p>
                                    )}
                                    {errors.department && <p className="mt-1 text-sm text-red-600">{errors.department}</p>}
                                </div>

                                {/* Phone */}
                                <div>
                                    <label htmlFor="phone" className="block font-medium text-sm text-gray-700">
                                        เบอร์โทรศัพท์ (ไม่บังคับ)
                                    </label>
                                    <input
                                        id="phone"
                                        type="text"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    />
                                    {errors.phone && <p className="mt-1 text-sm text-red-600">{errors.phone}</p>}
                                </div>

                                <div className="flex items-center justify-end pt-4 gap-3">
                                    <Link
                                        href={route('users.index')}
                                        className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150"
                                    >
                                        ยกเลิก
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150 shadow-sm disabled:opacity-50"
                                    >
                                        {processing ? 'กำลังบันทึก...' : 'บันทึก'}
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
