import React, { useState, useEffect } from 'react';
import { Head, usePage, router, Link } from '@inertiajs/react';

export default function Edit({ companies = [] }) {
    const { auth, errors = {}, status } = usePage().props;
    const user = auth?.user || {};

    // Tab state: 'all' | 'profile' | 'password'
    const [activeTab, setActiveTab] = useState('all');

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

    // Password visibility toggles
    const [showCurrentPassword, setShowCurrentPassword] = useState(false);
    const [showNewPassword, setShowNewPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    // Email verification state
    const [sendingVerification, setSendingVerification] = useState(false);

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
        setSendingVerification(true);
        router.post(
            route('verification.send'),
            {},
            {
                preserveScroll: true,
                onFinish: () => setSendingVerification(false),
            }
        );
    };

    // Helper to format dates in Thai (dd/MMMM/yyyy)
    const formatThaiDate = (dateString, includeTime = false) => {
        if (!dateString) return 'ไม่ระบุ';
        const d = new Date(dateString);
        if (isNaN(d.getTime())) return 'ไม่ระบุ';
        const months = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
        ];
        const dateFormatted = `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
        if (!includeTime) return dateFormatted;
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        return `${dateFormatted} เวลา ${hours}:${minutes} น.`;
    };

    // User initials helper
    const getInitials = (fullName) => {
        if (!fullName) return 'U';
        const parts = fullName.trim().split(/\s+/);
        if (parts.length >= 2) {
            return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
        }
        return fullName.slice(0, 2).toUpperCase();
    };

    // Role display information helper
    const getRoleDetails = (role) => {
        const roleMap = {
            administrator: {
                title: 'System Administrator',
                desc: 'ผู้ดูแลระบบสูงสุด มีสิทธิ์จัดการระบบ ผู้ใช้งาน และโครงสร้างทั้งหมด',
                pill: 'bg-purple-100 text-purple-800 border-purple-200',
                badgeBg: 'bg-purple-600',
            },
            manager: {
                title: 'IT Service Manager (Tier 3)',
                desc: 'ผู้จัดการฝ่ายบริการไอที ควบคุม SLA รายงาน และอนุมัติมาตรการ P-CAR',
                pill: 'bg-violet-100 text-violet-800 border-violet-200',
                badgeBg: 'bg-violet-600',
            },
            manager_software: {
                title: 'Software Manager (Tier 3)',
                desc: 'ผู้จัดการฝ่ายซอฟต์แวร์ ควบคุมและกำกับดูแลงานพัฒนาระบบ',
                pill: 'bg-violet-100 text-violet-800 border-violet-200',
                badgeBg: 'bg-violet-600',
            },
            manager_network_hardware: {
                title: 'Network & Hardware Manager (Tier 3)',
                desc: 'ผู้จัดการฝ่ายเครือข่ายและฮาร์ดแวร์ กำกับดูแลโครงสร้างพื้นฐานไอที',
                pill: 'bg-violet-100 text-violet-800 border-violet-200',
                badgeBg: 'bg-violet-600',
            },
            technician: {
                title: 'Technical Specialist (Tier 2)',
                desc: 'ช่างผู้เชี่ยวชาญเฉพาะทาง แก้ไขปัญหาเชิงลึก และวิเคราะห์สาเหตุ Root Cause',
                pill: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                badgeBg: 'bg-emerald-600',
            },
            team_hardware: {
                title: 'Hardware Specialist (Tier 2)',
                desc: 'ช่างเฉพาะทางด้านอุปกรณ์และฮาร์ดแวร์คอมพิวเตอร์',
                pill: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                badgeBg: 'bg-emerald-600',
            },
            team_network: {
                title: 'Network Specialist (Tier 2)',
                desc: 'ช่างเฉพาะทางด้านระบบเครือข่ายและการสื่อสาร',
                pill: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                badgeBg: 'bg-emerald-600',
            },
            team_software: {
                title: 'Software Specialist (Tier 2)',
                desc: 'ช่างเฉพาะทางด้านระบบโปรแกรมและฐานข้อมูล',
                pill: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                badgeBg: 'bg-emerald-600',
            },
            helpdesk: {
                title: 'Service Desk Agent (Tier 1)',
                desc: 'เจ้าหน้าที่รับแจ้งและคัดกรองเคส (Triage) ประสานงานและแก้ไขปัญหาเบื้องต้น',
                pill: 'bg-sky-100 text-sky-800 border-sky-200',
                badgeBg: 'bg-sky-600',
            },
            user: {
                title: 'End User / ผู้ใช้งานทั่วไป',
                desc: 'ผู้ใช้งานระบบ สามารถเปิดเคสแจ้งซ่อม ติดตามสถานะ และประเมินความพึงพอใจ',
                pill: 'bg-slate-100 text-slate-800 border-slate-200',
                badgeBg: 'bg-slate-700',
            },
        };

        return (
            roleMap[role] || {
                title: role || 'ผู้ใช้งาน',
                desc: 'ผู้ใช้งานระบบ IT Service Desk',
                pill: 'bg-slate-100 text-slate-800 border-slate-200',
                badgeBg: 'bg-slate-700',
            }
        );
    };

    const roleInfo = getRoleDetails(user.role);

    // Extract field error whether direct or in updatePassword bag
    const getFieldError = (field) => {
        if (errors[field]) return errors[field];
        if (errors.updatePassword && errors.updatePassword[field]) {
            return errors.updatePassword[field];
        }
        return null;
    };

    // Password criteria helpers
    const hasMinLength = newPassword.length >= 8;
    const passwordsMatch = newPassword.length > 0 && newPassword === confirmPassword;

    return (
        <>
            <Head title="ข้อมูลส่วนตัว (Profile)" />

            <div className="py-6 sm:py-10 bg-slate-50 min-h-screen">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                    {/* Top Navigation Bar / Breadcrumb */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
                        <div>
                            <div className="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                                <Link
                                    href={route('dashboard')}
                                    className="hover:text-indigo-600 transition-colors flex items-center gap-1"
                                >
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    แดชบอร์ด
                                </Link>
                                <span>/</span>
                                <span className="text-slate-900 font-bold">ข้อมูลส่วนตัว</span>
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                                <span>ข้อมูลส่วนตัว (Profile Settings)</span>
                            </h1>
                            <p className="text-xs sm:text-sm text-slate-500 mt-1">
                                จัดการข้อมูลประจำตัว สังกัดองค์กร และการตั้งค่าความปลอดภัยของบัญชีผู้ใช้งาน
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            <Link
                                href={route('dashboard')}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-2xs transition-all"
                            >
                                <svg className="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                กลับสู่หน้าหลัก
                            </Link>
                        </div>
                    </div>

                    {/* Status Alerts */}
                    {status === 'profile-updated' && (
                        <div className="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/80 text-emerald-900 p-4 sm:p-5 rounded-2xl shadow-xs flex items-center justify-between gap-3 animate-fadeIn">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-bold text-emerald-950">บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว</p>
                                    <p className="text-xs text-emerald-700 mt-0.5">ข้อมูลประจำตัวและสังกัดของคุณได้รับการอัปเดตลงระบบเรียบร้อยแล้ว</p>
                                </div>
                            </div>
                        </div>
                    )}

                    {status === 'password-updated' && (
                        <div className="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/80 text-emerald-900 p-4 sm:p-5 rounded-2xl shadow-xs flex items-center justify-between gap-3 animate-fadeIn">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-bold text-emerald-950">เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว</p>
                                    <p className="text-xs text-emerald-700 mt-0.5">รหัสผ่านใหม่ของคุณมีผลใช้งานทันที กรุณาใช้รหัสผ่านใหม่ในการเข้าสู่ระบบครั้งถัดไป</p>
                                </div>
                            </div>
                        </div>
                    )}

                    {status === 'verification-link-sent' && (
                        <div className="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200/80 text-blue-900 p-4 sm:p-5 rounded-2xl shadow-xs flex items-center justify-between gap-3 animate-fadeIn">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-bold text-blue-950">ลิงก์ยืนยันตัวตนใหม่ถูกส่งเรียบร้อยแล้ว</p>
                                    <p className="text-xs text-blue-700 mt-0.5">กรุณาตรวจสอบกล่องข้อความหรืออีเมลขยะ (Spam) ที่ {email}</p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Hero Header Card */}
                    <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white shadow-xl border border-slate-800 p-6 sm:p-8">
                        {/* Decorative Background Elements */}
                        <div className="absolute top-0 right-0 -mt-12 -mr-12 w-96 h-96 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none" />
                        <div className="absolute bottom-0 left-1/3 -mb-16 w-80 h-80 rounded-full bg-violet-500/10 blur-3xl pointer-events-none" />

                        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div className="flex flex-col sm:flex-row sm:items-center gap-5">
                                {/* Large Avatar with Status Beacon */}
                                <div className="relative shrink-0 self-start sm:self-auto">
                                    <div className="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-tr from-indigo-500 via-indigo-600 to-violet-600 flex items-center justify-center text-white font-black text-2xl sm:text-3xl shadow-lg ring-4 ring-white/10">
                                        {getInitials(user.name)}
                                    </div>
                                    <span
                                        className="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 border-2 border-slate-900 rounded-full shadow-xs flex items-center justify-center"
                                        title="Active Account"
                                    >
                                        <span className="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </span>
                                </div>

                                {/* User Meta Info */}
                                <div className="space-y-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="text-xl sm:text-2xl font-black text-white tracking-tight">
                                            {user.name || 'ไม่ระบุชื่อ'}
                                        </h2>
                                        <span
                                            className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold tracking-wide border shadow-2xs ${roleInfo.pill}`}
                                        >
                                            <span className={`w-1.5 h-1.5 rounded-full ${roleInfo.badgeBg}`}></span>
                                            {roleInfo.title}
                                        </span>
                                    </div>

                                    {/* Subtitle Chips */}
                                    <div className="flex flex-wrap items-center gap-y-1.5 gap-x-4 text-xs text-slate-300">
                                        <div className="flex items-center gap-1.5">
                                            <svg className="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            <span className="font-medium text-white">{user.company || 'ไม่ระบุสังกัด'}</span>
                                        </div>

                                        <div className="flex items-center gap-1.5">
                                            <svg className="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            <span>แผนก: <strong className="text-white font-semibold">{user.department || 'ไม่ระบุ'}</strong></span>
                                        </div>

                                        <div className="flex items-center gap-1.5">
                                            <svg className="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            <span className="font-mono text-slate-200">{user.email}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Badges in Hero */}
                            <div className="flex flex-wrap md:flex-col md:items-end gap-2 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-slate-800">
                                {user.email_verified_at ? (
                                    <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>ยืนยันอีเมลแล้ว</span>
                                    </div>
                                ) : (
                                    <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span>ยังไม่ยืนยันอีเมล</span>
                                    </div>
                                )}

                                <div className="text-[11px] text-slate-400">
                                    เข้าใช้งานล่าสุด: <span className="text-slate-200 font-medium">{formatThaiDate(user.last_login_at, true)}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Main Content Grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 items-start">
                        {/* Left Column: Account Details & Identity Card */}
                        <div className="lg:col-span-4 space-y-6">
                            {/* Card 1: Identity & Role Overview */}
                            <div className="bg-white rounded-2xl shadow-xs border border-slate-200/90 p-5 sm:p-6 space-y-5">
                                <div className="flex items-center gap-3 pb-3 border-b border-slate-100">
                                    <div className="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-bold text-slate-900">สรุปข้อมูลบัญชีผู้ใช้งาน</h3>
                                        <p className="text-[11px] text-slate-500">สถานะและสิทธิ์การเข้าถึงในระบบ</p>
                                    </div>
                                </div>

                                {/* Role Information Description */}
                                <div className="p-3.5 bg-slate-50 rounded-xl border border-slate-200/70 space-y-1.5">
                                    <div className="flex items-center justify-between">
                                        <span className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">บทบาทหน้าที่ (Role)</span>
                                        <span className={`px-2 py-0.5 rounded-md text-[10px] font-bold ${roleInfo.pill}`}>
                                            {user.role}
                                        </span>
                                    </div>
                                    <p className="text-xs font-bold text-slate-800">{roleInfo.title}</p>
                                    <p className="text-[11px] text-slate-600 leading-relaxed">{roleInfo.desc}</p>
                                </div>

                                {/* Teams (if applicable) */}
                                {user.teams && user.teams.length > 0 && (
                                    <div className="space-y-1.5 pt-1">
                                        <label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                                            ทีมเฉพาะทางที่สังกัด (Teams)
                                        </label>
                                        <div className="flex flex-wrap gap-1.5">
                                            {user.teams.map((t) => (
                                                <span
                                                    key={t.id}
                                                    className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-50 text-teal-800 border border-teal-200 text-xs font-semibold"
                                                >
                                                    <span className="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                                                    {t.name}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Key Meta Information Rows */}
                                <div className="space-y-3 pt-2 text-xs">
                                    <div className="flex items-center justify-between py-1.5 border-b border-slate-100">
                                        <span className="text-slate-500 flex items-center gap-1.5">
                                            <svg className="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            บริษัท
                                        </span>
                                        <span className="font-semibold text-slate-800 text-right">{user.company || '-'}</span>
                                    </div>

                                    <div className="flex items-center justify-between py-1.5 border-b border-slate-100">
                                        <span className="text-slate-500 flex items-center gap-1.5">
                                            <svg className="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            แผนก
                                        </span>
                                        <span className="font-semibold text-slate-800 text-right">{user.department || '-'}</span>
                                    </div>

                                    <div className="flex items-center justify-between py-1.5 border-b border-slate-100">
                                        <span className="text-slate-500 flex items-center gap-1.5">
                                            <svg className="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                            เบอร์โทรศัพท์
                                        </span>
                                        <span className="font-semibold text-slate-800 text-right">{user.phone || '-'}</span>
                                    </div>

                                    <div className="flex items-center justify-between py-1.5 border-b border-slate-100">
                                        <span className="text-slate-500 flex items-center gap-1.5">
                                            <svg className="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            การเชื่อมต่อ LINE OA
                                        </span>
                                        {user.line_user_id ? (
                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                เชื่อมต่อแล้ว
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-bold border border-slate-200">
                                                ยังไม่เชื่อมต่อ
                                            </span>
                                        )}
                                    </div>

                                    <div className="flex items-center justify-between py-1.5 border-b border-slate-100">
                                        <span className="text-slate-500 flex items-center gap-1.5">
                                            <svg className="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            วันที่สมัครสมาชิก
                                        </span>
                                        <span className="font-medium text-slate-700 text-right">{formatThaiDate(user.created_at)}</span>
                                    </div>

                                    <div className="flex items-center justify-between py-1.5">
                                        <span className="text-slate-500 flex items-center gap-1.5">
                                            <svg className="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            เข้าสู่ระบบล่าสุด
                                        </span>
                                        <span className="font-medium text-slate-700 text-right">{formatThaiDate(user.last_login_at, true)}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Card 2: Security Guidelines */}
                            <div className="bg-gradient-to-br from-slate-900 to-indigo-950 text-white rounded-2xl p-5 sm:p-6 space-y-4 shadow-md">
                                <div className="flex items-center gap-2.5">
                                    <div className="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-indigo-300">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-bold text-white uppercase tracking-wider">ข้อแนะนำด้านความปลอดภัย</h4>
                                        <p className="text-[10px] text-slate-400">Security Best Practices</p>
                                    </div>
                                </div>

                                <ul className="text-xs text-slate-300 space-y-2 leading-relaxed">
                                    <li className="flex items-start gap-2">
                                        <span className="text-indigo-400 font-bold">•</span>
                                        <span>ใช้รหัสผ่านความยาวอย่างน้อย 8 ตัวอักษร โดยผสมตัวอักษรพิมพ์ใหญ่ พิมพ์เล็ก ตัวเลข และสัญลักษณ์</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="text-indigo-400 font-bold">•</span>
                                        <span>ไม่แชร์รหัสผ่านหรือส่งต่อให้ผู้อื่น เพื่อรักษาความปลอดภัยของข้อมูลใบงานและการดำเนินงาน</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="text-indigo-400 font-bold">•</span>
                                        <span>หมั่นตรวจสอบเบอร์โทรศัพท์และอีเมลให้เป็นปัจจุบันเสมอ เพื่อไม่พลาดการแจ้งเตือนเคสสำคัญ</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {/* Right Column: Forms & Settings */}
                        <div className="lg:col-span-8 space-y-6">
                            {/* Tab Switcher */}
                            <div className="flex items-center p-1 bg-white rounded-2xl border border-slate-200/90 shadow-2xs">
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('all')}
                                    className={`flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 ${
                                        activeTab === 'all'
                                            ? 'bg-indigo-600 text-white shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                    }`}
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h7" />
                                    </svg>
                                    <span>แสดงทั้งหมด (All)</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('profile')}
                                    className={`flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 ${
                                        activeTab === 'profile'
                                            ? 'bg-indigo-600 text-white shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                    }`}
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>ข้อมูลส่วนตัว (Profile)</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('password')}
                                    className={`flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 ${
                                        activeTab === 'password'
                                            ? 'bg-indigo-600 text-white shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                    }`}
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span>เปลี่ยนรหัสผ่าน (Password)</span>
                                </button>
                            </div>

                            {/* Section 1: Profile Information */}
                            {(activeTab === 'all' || activeTab === 'profile') && (
                                <div className="bg-white rounded-2xl shadow-xs border border-slate-200/90 p-6 sm:p-8 space-y-6">
                                    {/* Card Header */}
                                    <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-100 gap-3">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-500/20 flex items-center justify-center shrink-0">
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 className="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                                    <span>ข้อมูลบัญชีผู้ใช้งาน (Profile Information)</span>
                                                </h3>
                                                <p className="text-xs text-slate-500 mt-0.5">
                                                    แก้ไขชื่อ นามสกุล สังกัดบริษัท แผนก และข้อมูลการติดต่อของคุณ
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Profile Form */}
                                    <form onSubmit={handleProfileSubmit} className="space-y-5">
                                        {/* Name Field */}
                                        <div>
                                            <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                ชื่อ-นามสกุล (Name) <span className="text-rose-500">*</span>
                                            </label>
                                            {user.role === 'administrator' ? (
                                                <div className="space-y-2">
                                                    <div className="relative">
                                                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                            </svg>
                                                        </div>
                                                        <input
                                                            type="text"
                                                            value={name}
                                                            readOnly
                                                            disabled
                                                            className="w-full bg-slate-100 text-slate-600 border border-slate-200 rounded-xl shadow-2xs cursor-not-allowed text-sm py-2.5 pl-10 pr-4"
                                                        />
                                                    </div>
                                                    <div className="p-3 bg-amber-50/80 border border-amber-200/70 rounded-xl text-xs text-amber-900 flex items-start gap-2.5">
                                                        <svg className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        <span>
                                                            <strong>สงวนสิทธิ์ผู้ดูแลระบบ:</strong> บัญชีระดับ Administrator ไม่สามารถเปลี่ยนชื่อได้โดยตรงผ่านหน้าเว็บ เพื่อความถูกต้องโปร่งใสของประวัติการทำงาน (Audit Log)
                                                        </span>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="relative">
                                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
                                                    <input
                                                        type="text"
                                                        value={name}
                                                        onChange={(e) => setName(e.target.value)}
                                                        required
                                                        placeholder="ระบุชื่อและนามสกุลของคุณ"
                                                        className="w-full border-slate-300 rounded-xl shadow-2xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm py-2.5 pl-10 pr-4 transition duration-200"
                                                    />
                                                </div>
                                            )}
                                            {getFieldError('name') && <p className="mt-1 text-xs text-rose-600">{getFieldError('name')}</p>}
                                        </div>

                                        {/* Company & Department (2 Columns) */}
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            {/* Company */}
                                            <div>
                                                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                    สังกัดบริษัท (Company) <span className="text-rose-500">*</span>
                                                </label>
                                                <div className="relative">
                                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                        </svg>
                                                    </div>
                                                    <select
                                                        value={company}
                                                        onChange={(e) => handleCompanyChange(e.target.value)}
                                                        required
                                                        className="w-full border-slate-300 rounded-xl shadow-2xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm py-2.5 pl-10 pr-8 bg-white transition duration-200"
                                                    >
                                                        <option value="" disabled>เลือกบริษัทของคุณ</option>
                                                        {companies.map((c) => (
                                                            <option key={c.id} value={c.name}>
                                                                {c.name}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                                {getFieldError('company') && <p className="mt-1 text-xs text-rose-600">{getFieldError('company')}</p>}
                                            </div>

                                            {/* Department */}
                                            <div>
                                                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                    แผนก (Department) <span className="text-rose-500">*</span>
                                                </label>

                                                {!isCustomDept && companyDepartments.length > 0 ? (
                                                    <div className="relative">
                                                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                            </svg>
                                                        </div>
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
                                                            className="w-full border-slate-300 rounded-xl shadow-2xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm py-2.5 pl-10 pr-8 bg-white transition duration-200"
                                                        >
                                                            <option value="">กรุณาเลือกแผนก</option>
                                                            {companyDepartments.map((dept) => (
                                                                <option key={dept.id} value={dept.name}>
                                                                    {dept.name}
                                                                </option>
                                                            ))}
                                                            <option value="__other__">✏️ อื่นๆ (พิมพ์ระบุเอง)</option>
                                                        </select>
                                                    </div>
                                                ) : (
                                                    <div className="relative">
                                                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                            </svg>
                                                        </div>
                                                        <input
                                                            type="text"
                                                            value={customDepartment}
                                                            onChange={(e) => setCustomDepartment(e.target.value)}
                                                            required
                                                            placeholder="ระบุแผนกของคุณ"
                                                            className="w-full border-slate-300 rounded-xl shadow-2xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm py-2.5 pl-10 pr-24 transition duration-200"
                                                        />
                                                        {companyDepartments.length > 0 && (
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    setIsCustomDept(false);
                                                                    setDepartment(companyDepartments[0]?.name || '');
                                                                }}
                                                                className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs text-indigo-600 hover:text-indigo-800 font-bold transition-colors"
                                                            >
                                                                กลับไปเลือก
                                                            </button>
                                                        )}
                                                    </div>
                                                )}
                                                {getFieldError('department') && <p className="mt-1 text-xs text-rose-600">{getFieldError('department')}</p>}
                                            </div>
                                        </div>

                                        {/* Phone & Email (2 Columns) */}
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            {/* Phone */}
                                            <div>
                                                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                    เบอร์โทรศัพท์ติดต่อ (Phone)
                                                </label>
                                                <div className="relative">
                                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                        </svg>
                                                    </div>
                                                    <input
                                                        type="text"
                                                        value={phone}
                                                        onChange={(e) => setPhone(e.target.value)}
                                                        placeholder="เช่น 081-234-5678 หรือ เบอร์ต่อภายใน"
                                                        className="w-full border-slate-300 rounded-xl shadow-2xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm py-2.5 pl-10 pr-4 transition duration-200"
                                                    />
                                                </div>
                                                {getFieldError('phone') && <p className="mt-1 text-xs text-rose-600">{getFieldError('phone')}</p>}
                                            </div>

                                            {/* Email */}
                                            <div>
                                                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                    อีเมลประจำตัว (Email) <span className="text-rose-500">*</span>
                                                </label>
                                                <div className="relative">
                                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                                        </svg>
                                                    </div>
                                                    <input
                                                        type="email"
                                                        value={email}
                                                        onChange={(e) => setEmail(e.target.value)}
                                                        required
                                                        placeholder="name@company.com"
                                                        className="w-full border-slate-300 rounded-xl shadow-2xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm py-2.5 pl-10 pr-4 font-mono transition duration-200"
                                                    />
                                                </div>
                                                {getFieldError('email') && <p className="mt-1 text-xs text-rose-600">{getFieldError('email')}</p>}
                                            </div>
                                        </div>

                                        {/* Email Unverified Warning Box */}
                                        {!user.email_verified_at && (
                                            <div className="p-4 bg-amber-50/80 border border-amber-200/80 rounded-2xl text-xs text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                                                <div className="flex items-center gap-2.5">
                                                    <svg className="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <div>
                                                        <span className="font-bold">อีเมลของคุณยังไม่ได้รับการยืนยันตัวตน</span>
                                                        <p className="text-[11px] text-amber-700 mt-0.5">การยืนยันอีเมลช่วยให้คุณสามารถกู้คืนรหัสผ่านและรับการแจ้งเตือนเคสได้อย่างปลอดภัย</p>
                                                    </div>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={handleResendVerification}
                                                    disabled={sendingVerification}
                                                    className="self-start sm:self-auto px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-xs shadow-xs transition-colors shrink-0 disabled:opacity-50 flex items-center gap-1.5"
                                                >
                                                    {sendingVerification && (
                                                        <svg className="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    )}
                                                    ส่งอีเมลยืนยันอีกครั้ง
                                                </button>
                                            </div>
                                        )}

                                        {/* Action Buttons */}
                                        <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                                            <button
                                                type="submit"
                                                disabled={profileSaving}
                                                className="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow-sm hover:shadow transition-all duration-200 disabled:opacity-50 cursor-pointer"
                                            >
                                                {profileSaving ? (
                                                    <>
                                                        <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <span>กำลังบันทึกข้อมูล...</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        <span>บันทึกข้อมูลส่วนตัว</span>
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            )}

                            {/* Section 2: Update Password */}
                            {(activeTab === 'all' || activeTab === 'password') && (
                                <div className="bg-white rounded-2xl shadow-xs border border-slate-200/90 p-6 sm:p-8 space-y-6">
                                    {/* Card Header */}
                                    <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-100 gap-3">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-xl bg-slate-100 text-slate-800 ring-1 ring-slate-300 flex items-center justify-center shrink-0">
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 className="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                                    <span>ความปลอดภัยและรหัสผ่าน (Security & Password)</span>
                                                </h3>
                                                <p className="text-xs text-slate-500 mt-0.5">
                                                    ตั้งรหัสผ่านใหม่ที่มีความปลอดภัยสูง เพื่อปกป้องบัญชีและข้อมูลของคุณ
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Password Form */}
                                    <form onSubmit={handlePasswordSubmit} className="space-y-5">
                                        {/* Current Password */}
                                        <div>
                                            <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                รหัสผ่านปัจจุบัน (Current Password) <span className="text-rose-500">*</span>
                                            </label>
                                            <div className="relative">
                                                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <input
                                                    type={showCurrentPassword ? 'text' : 'password'}
                                                    value={currentPassword}
                                                    onChange={(e) => setCurrentPassword(e.target.value)}
                                                    required
                                                    autoComplete="current-password"
                                                    placeholder="กรอกรหัสผ่านปัจจุบันของคุณ"
                                                    className="w-full border-slate-300 rounded-xl shadow-2xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm py-2.5 pl-10 pr-10 transition duration-200 font-mono"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                                                    className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                                                    title={showCurrentPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'}
                                                >
                                                    {showCurrentPassword ? (
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                                        </svg>
                                                    ) : (
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    )}
                                                </button>
                                            </div>
                                            {getFieldError('current_password') && (
                                                <p className="mt-1 text-xs text-rose-600">{getFieldError('current_password')}</p>
                                            )}
                                        </div>

                                        {/* New Password & Confirm Password (2 Columns) */}
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            {/* New Password */}
                                            <div>
                                                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                    รหัสผ่านใหม่ (New Password) <span className="text-rose-500">*</span>
                                                </label>
                                                <div className="relative">
                                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                        </svg>
                                                    </div>
                                                    <input
                                                        type={showNewPassword ? 'text' : 'password'}
                                                        value={newPassword}
                                                        onChange={(e) => setNewPassword(e.target.value)}
                                                        required
                                                        autoComplete="new-password"
                                                        placeholder="ความยาวอย่างน้อย 8 ตัวอักษร"
                                                        className="w-full border-slate-300 rounded-xl shadow-2xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm py-2.5 pl-10 pr-10 transition duration-200 font-mono"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => setShowNewPassword(!showNewPassword)}
                                                        className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                                                        title={showNewPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'}
                                                    >
                                                        {showNewPassword ? (
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                                        </svg>
                                                        ) : (
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        )}
                                                    </button>
                                                </div>
                                                {getFieldError('password') && <p className="mt-1 text-xs text-rose-600">{getFieldError('password')}</p>}
                                            </div>

                                            {/* Confirm Password */}
                                            <div>
                                                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                    ยืนยันรหัสผ่านใหม่ (Confirm) <span className="text-rose-500">*</span>
                                                </label>
                                                <div className="relative">
                                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                        </svg>
                                                    </div>
                                                    <input
                                                        type={showConfirmPassword ? 'text' : 'password'}
                                                        value={confirmPassword}
                                                        onChange={(e) => setConfirmPassword(e.target.value)}
                                                        required
                                                        autoComplete="new-password"
                                                        placeholder="พิมพ์รหัสผ่านใหม่อีกครั้ง"
                                                        className="w-full border-slate-300 rounded-xl shadow-2xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm py-2.5 pl-10 pr-10 transition duration-200 font-mono"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                                                        className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                                                        title={showConfirmPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'}
                                                    >
                                                        {showConfirmPassword ? (
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                                        </svg>
                                                        ) : (
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        )}
                                                    </button>
                                                </div>
                                                {getFieldError('password_confirmation') && (
                                                    <p className="mt-1 text-xs text-rose-600">{getFieldError('password_confirmation')}</p>
                                                )}
                                            </div>
                                        </div>

                                        {/* Dynamic Password Validation Indicators */}
                                        {newPassword.length > 0 && (
                                            <div className="p-3.5 bg-slate-50 border border-slate-200/70 rounded-xl space-y-1.5">
                                                <p className="text-[11px] font-bold text-slate-600 uppercase tracking-wider">เกณฑ์ความปลอดภัยของรหัสผ่าน</p>
                                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                                    <div className="flex items-center gap-2">
                                                        {hasMinLength ? (
                                                            <svg className="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        ) : (
                                                            <span className="w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center shrink-0 text-[10px] text-slate-400">○</span>
                                                        )}
                                                        <span className={hasMinLength ? 'text-emerald-700 font-semibold' : 'text-slate-500'}>
                                                            ความยาวอย่างน้อย 8 ตัวอักษร
                                                        </span>
                                                    </div>

                                                    <div className="flex items-center gap-2">
                                                        {passwordsMatch ? (
                                                            <svg className="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        ) : (
                                                            <span className="w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center shrink-0 text-[10px] text-slate-400">○</span>
                                                        )}
                                                        <span className={passwordsMatch ? 'text-emerald-700 font-semibold' : 'text-slate-500'}>
                                                            รหัสผ่านทั้งสองช่องตรงกัน
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* Submit Button */}
                                        <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                                            <button
                                                type="submit"
                                                disabled={passwordSaving || (newPassword.length > 0 && (!hasMinLength || !passwordsMatch))}
                                                className="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow-sm hover:shadow transition-all duration-200 disabled:opacity-50 cursor-pointer"
                                            >
                                                {passwordSaving ? (
                                                    <>
                                                        <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <span>กำลังเปลี่ยนรหัสผ่าน...</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                        </svg>
                                                        <span>เปลี่ยนรหัสผ่านใหม่</span>
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
