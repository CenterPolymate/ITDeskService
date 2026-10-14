import React, { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';

export default function Edit({ companies = [] }) {
    const { auth, flash, errors, status } = usePage().props;
    const user = auth.user;

    // Profile form state
    const [name, setName] = useState(user.name || '');
    const [company, setCompany] = useState(user.company || '');
    const [department, setDepartment] = useState(user.department || '');
    const [isCustomDept, setIsCustomDept] = useState(false);
    const [customDepartment, setCustomDepartment] = useState('');
    const [phone, setPhone] = useState(user.phone || '');
    const [email, setEmail] = useState(user.email || '');
    const [profileSaving, setProfileSaving] = useState(false);

    // Password form state
    const [currentPassword, setCurrentPassword] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [passwordSaving, setPasswordSaving] = useState(false);

    // Find current company object
    const selectedCompanyObj = companies.find((c) => c.name === company);
    const companyDepartments = selectedCompanyObj?.departments || [];

    // Initialize department selection logic
    useEffect(() => {
        if (!companyDepartments.length) {
            setIsCustomDept(true);
            setCustomDepartment(department);
        } else {
            const match = companyDepartments.find((d) => d.name === department);
            if (match) {
                setIsCustomDept(false);
            } else if (department) {
                setIsCustomDept(true);
                setCustomDepartment(department);
            }
        }
    }, [company]);

    const handleCompanyChange = (newCompany) => {
        setCompany(newCompany);
        const comp = companies.find((c) => c.name === newCompany);
        const depts = comp?.departments || [];
        if (depts.length > 0) {
            setDepartment(depts[0].name);
            setIsCustomDept(false);
            setCustomDepartment('');
        } else {
            setDepartment('');
            setIsCustomDept(true);
            setCustomDepartment('');
        }
    };

    const handleProfileSubmit = (e) => {
        e.preventDefault();
        setProfileSaving(true);

        const deptValue = isCustomDept ? customDepartment : department;

        router.patch(
            route('profile.update'),
            {
                name,
                company,
                department: deptValue,
                phone,
                email,
            },
            {
                preserveScroll: true,
                onFinish: () => setProfileSaving(false),
            }
        );
    };

    const handlePasswordSubmit = (e) => {
        e.preventDefault();
        setPasswordSaving(true);

        router.put(
            route('password.update'),
            {
                current_password: currentPassword,
                password: newPassword,
                password_confirmation: confirmPassword,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCurrentPassword('');
                    setNewPassword('');
                    setConfirmPassword('');
                },
                onFinish: () => setPasswordSaving(false),
            }
        );
    };

    const handleResendVerification = (e) => {
        e.preventDefault();
        router.post(route('verification.send'), {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title="ข้อมูลส่วนตัว (Profile)" />

            <div className="py-6 sm:py-8 bg-gray-50 min-h-screen">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div>
                        <h1 className="text-2xl font-black text-gray-900 tracking-tight">
                            ข้อมูลส่วนตัว (Profile)
                        </h1>
                        <p className="text-sm text-gray-500 mt-1">
                            จัดการข้อมูลบัญชีผู้ใช้งาน สังกัดบริษัท แผนก และการตั้งค่ารหัสผ่าน
                        </p>
                    </div>

                    {/* Flash & Status messages */}
                    {status === 'profile-updated' && (
                        <div className="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-xs flex items-center gap-2">
                            <svg className="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span className="text-sm font-semibold">บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว</span>
                        </div>
                    )}

                    {status === 'password-updated' && (
                        <div className="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-xs flex items-center gap-2">
                            <svg className="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span className="text-sm font-semibold">เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว</span>
                        </div>
                    )}

                    {status === 'verification-link-sent' && (
                        <div className="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-r-xl shadow-xs flex items-center gap-2">
                            <svg className="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span className="text-sm font-semibold">ลิงก์ยืนยันตัวตนใหม่ถูกส่งไปยังอีเมลของคุณแล้ว</span>
                        </div>
                    )}

                    {/* Section 1: Profile Information */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-6 sm:p-8">
                        <div className="border-b border-gray-100 pb-3 mb-6">
                            <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <span className="w-2 h-2 rounded-full bg-indigo-500"></span>
                                ข้อมูลบัญชีผู้ใช้ (Profile Information)
                            </h2>
                            <p className="mt-1 text-xs text-gray-500">
                                แก้ไขชื่อ สังกัดบริษัท แผนก และที่อยู่อีเมลของคุณ
                            </p>
                        </div>

                        <form onSubmit={handleProfileSubmit} className="space-y-5 max-w-xl">
                            {/* Name */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    ชื่อ-นามสกุล (Name) <span className="text-rose-500">*</span>
                                </label>
                                {user.role === 'administrator' ? (
                                    <>
                                        <input
                                            type="text"
                                            value={name}
                                            readOnly
                                            disabled
                                            className="w-full bg-gray-100 text-gray-500 border-gray-300 rounded-lg shadow-xs cursor-not-allowed text-sm"
                                        />
                                        <p className="mt-1 text-xs text-gray-400">
                                            🔒 บัญชีผู้ดูแลระบบ (Administrator) ไม่สามารถเปลี่ยนชื่อได้โดยตรง
                                        </p>
                                    </>
                                ) : (
                                    <input
                                        type="text"
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                        required
                                        className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                    />
                                )}
                                {errors.name && <p className="mt-1 text-xs text-rose-600">{errors.name}</p>}
                            </div>

                            {/* Company */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    บริษัท (Company) <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={company}
                                    onChange={(e) => handleCompanyChange(e.target.value)}
                                    required
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                >
                                    <option value="" disabled>เลือกบริษัทของคุณ</option>
                                    {companies.map((c) => (
                                        <option key={c.id} value={c.name}>
                                            {c.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.company && <p className="mt-1 text-xs text-rose-600">{errors.company}</p>}
                            </div>

                            {/* Department */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    แผนก (Department) <span className="text-rose-500">*</span>
                                </label>

                                {!isCustomDept && companyDepartments.length > 0 ? (
                                    <div className="space-y-2">
                                        <select
                                            value={department}
                                            onChange={(e) => {
                                                if (e.target.value === '__other__') {
                                                    setIsCustomDept(true);
                                                    setCustomDepartment('');
                                                } else {
                                                    setDepartment(e.target.value);
                                                }
                                            }}
                                            required
                                            className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                        >
                                            <option value="">กรุณาเลือกแผนก</option>
                                            {companyDepartments.map((dept) => (
                                                <option key={dept.id} value={dept.name}>
                                                    {dept.name}
                                                </option>
                                            ))}
                                            <option value="__other__">อื่นๆ (พิมพ์ระบุเอง)</option>
                                        </select>
                                    </div>
                                ) : (
                                    <div className="relative">
                                        <input
                                            type="text"
                                            value={customDepartment}
                                            onChange={(e) => setCustomDepartment(e.target.value)}
                                            required
                                            placeholder="ระบุแผนกของคุณ"
                                            className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm pr-24"
                                        />
                                        {companyDepartments.length > 0 && (
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setIsCustomDept(false);
                                                    setDepartment(companyDepartments[0]?.name || '');
                                                }}
                                                className="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-indigo-600 hover:text-indigo-800 font-bold"
                                            >
                                                กลับไปเลือก
                                            </button>
                                        )}
                                    </div>
                                )}
                                {errors.department && <p className="mt-1 text-xs text-rose-600">{errors.department}</p>}
                            </div>

                            {/* Phone */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    เบอร์โทรศัพท์ (Phone)
                                </label>
                                <input
                                    type="text"
                                    value={phone}
                                    onChange={(e) => setPhone(e.target.value)}
                                    placeholder="เช่น 081-234-5678"
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                />
                                {errors.phone && <p className="mt-1 text-xs text-rose-600">{errors.phone}</p>}
                            </div>

                            {/* Email */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    อีเมล (Email) <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    required
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm font-mono"
                                />
                                {errors.email && <p className="mt-1 text-xs text-rose-600">{errors.email}</p>}

                                {!user.email_verified_at && (
                                    <div className="mt-2.5 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-center justify-between gap-2">
                                        <span>อีเมลของคุณยังไม่ได้รับการยืนยันตัวตน</span>
                                        <button
                                            type="button"
                                            onClick={handleResendVerification}
                                            className="font-bold underline text-amber-900 hover:text-amber-700"
                                        >
                                            ส่งอีเมลยืนยันอีกครั้ง
                                        </button>
                                    </div>
                                )}
                            </div>

                            {/* Save button */}
                            <div className="pt-2">
                                <button
                                    type="submit"
                                    disabled={profileSaving}
                                    className="inline-flex items-center justify-center px-6 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-indigo-700 shadow-sm transition disabled:opacity-50"
                                >
                                    {profileSaving ? 'กำลังบันทึก...' : 'บันทึกข้อมูล'}
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Section 2: Update Password */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-6 sm:p-8">
                        <div className="border-b border-gray-100 pb-3 mb-6">
                            <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <span className="w-2 h-2 rounded-full bg-slate-600"></span>
                                เปลี่ยนรหัสผ่าน (Update Password)
                            </h2>
                            <p className="mt-1 text-xs text-gray-500">
                                ตรวจสอบให้แน่ใจว่าบัญชีของคุณใช้รหัสผ่านที่มีความปลอดภัยสูง
                            </p>
                        </div>

                        <form onSubmit={handlePasswordSubmit} className="space-y-5 max-w-xl">
                            <div>
                                <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    รหัสผ่านปัจจุบัน (Current Password) <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="password"
                                    value={currentPassword}
                                    onChange={(e) => setCurrentPassword(e.target.value)}
                                    required
                                    autoComplete="current-password"
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                />
                                {errors.current_password && (
                                    <p className="mt-1 text-xs text-rose-600">{errors.current_password}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    รหัสผ่านใหม่ (New Password) <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="password"
                                    value={newPassword}
                                    onChange={(e) => setNewPassword(e.target.value)}
                                    required
                                    autoComplete="new-password"
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                />
                                {errors.password && <p className="mt-1 text-xs text-rose-600">{errors.password}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    ยืนยันรหัสผ่านใหม่ (Confirm Password) <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="password"
                                    value={confirmPassword}
                                    onChange={(e) => setConfirmPassword(e.target.value)}
                                    required
                                    autoComplete="new-password"
                                    className="w-full border-gray-300 rounded-lg shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                />
                                {errors.password_confirmation && (
                                    <p className="mt-1 text-xs text-rose-600">{errors.password_confirmation}</p>
                                )}
                            </div>

                            <div className="pt-2">
                                <button
                                    type="submit"
                                    disabled={passwordSaving}
                                    className="inline-flex items-center justify-center px-6 py-2.5 bg-gray-900 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-black shadow-sm transition disabled:opacity-50"
                                >
                                    {passwordSaving ? 'กำลังเปลี่ยนรหัสผ่าน...' : 'เปลี่ยนรหัสผ่าน'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
