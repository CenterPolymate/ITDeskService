import React, { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { updateBrowserFavicon } from '../../Utils/branding';

export default function Index({ settings = {}, branding = {}, initialSmtpToken = '', savedVerified = false, diagnostics = {} }) {
    const { auth, flash, errors } = usePage().props;

    // Corporate Branding states
    const [appName, setAppName] = useState(settings.app_name || branding?.app_name || 'ITDeskService');
    const [supportContact, setSupportContact] = useState(settings.support_contact || branding?.support_contact || '');

    // Logo states
    const [appLogoFile, setAppLogoFile] = useState(null);
    const [appLogoPreview, setAppLogoPreview] = useState(branding?.app_logo || null);
    const [removeAppLogo, setRemoveAppLogo] = useState(false);

    // Login Logo states
    const [appLogoLoginFile, setAppLogoLoginFile] = useState(null);
    const [appLogoLoginPreview, setAppLogoLoginPreview] = useState(branding?.app_logo_login || null);
    const [removeAppLogoLogin, setRemoveAppLogoLogin] = useState(false);

    // Favicon states
    const [appFaviconFile, setAppFaviconFile] = useState(null);
    const [appFaviconPreview, setAppFaviconPreview] = useState(branding?.app_favicon || null);
    const [removeAppFavicon, setRemoveAppFavicon] = useState(false);

    const [saving, setSaving] = useState(false);

    const handleLogoChange = (file, setFile, setPreview, setRemove, isFavicon = false) => {
        if (!file) return;
        setFile(file);
        setRemove(false);
        const reader = new FileReader();
        reader.onloadend = () => {
            setPreview(reader.result);
            if (isFavicon) {
                updateBrowserFavicon(reader.result);
            }
        };
        reader.readAsDataURL(file);
    };

    const handleResetLogo = (setFile, setPreview, setRemove, isFavicon = false) => {
        setFile(null);
        setPreview(null);
        setRemove(true);
        if (isFavicon) {
            updateBrowserFavicon('/images/favicon.png');
        }
    };

    // Form states
    const [lineAccessToken, setLineAccessToken] = useState(settings.line_channel_access_token || '');
    const [lineSecret, setLineSecret] = useState(settings.line_channel_secret || '');

    const [smtpHost, setSmtpHost] = useState(settings.smtp_host || 'smtp.mailtrap.io');
    const [smtpPort, setSmtpPort] = useState(settings.smtp_port || '2525');
    const [smtpUsername, setSmtpUsername] = useState(settings.smtp_username || settings.smtp_from_address || '');
    const [smtpPassword, setSmtpPassword] = useState('');
    const [smtpEncryption, setSmtpEncryption] = useState(settings.smtp_encryption || 'ssl');

    const [auditRetentionYears, setAuditRetentionYears] = useState(settings.audit_log_retention_years || '1');

    // SLA Alert states
    const [slaAlertEnabled, setSlaAlertEnabled] = useState(settings.sla_alert_enabled !== '0');
    const [slaWarningThreshold, setSlaWarningThreshold] = useState(settings.sla_warning_threshold_minutes || '60');
    const [runningSlaCheck, setRunningSlaCheck] = useState(false);
    const [slaCheckFeedback, setSlaCheckFeedback] = useState(null);

    const [smtpVerifiedToken, setSmtpVerifiedToken] = useState(initialSmtpToken || '');
    const [isVerified, setIsVerified] = useState(savedVerified && Boolean(initialSmtpToken));
    const [lastTestResult, setLastTestResult] = useState(null);

    // Modal state
    const [showTestModal, setShowTestModal] = useState(false);
    const [testEmailTarget, setTestEmailTarget] = useState(auth?.user?.email || '');
    const [testing, setTesting] = useState(false);
    const [testFeedback, setTestFeedback] = useState(null);

    const isConfigured = smtpHost.trim() && smtpHost.trim() !== 'smtp.mailtrap.io';

    // Handle SMTP field changes - invalidate verification if changed
    const handleSmtpChange = (setter) => (e) => {
        setter(e.target.value);
        setIsVerified(false);
        setSmtpVerifiedToken('');
        setLastTestResult(null);
    };

    const handlePortChange = (e) => {
        const val = e.target.value.trim();
        setSmtpPort(val);
        if (val === '465') {
            setSmtpEncryption('ssl');
        } else if (val === '587') {
            setSmtpEncryption('tls');
        }
        setIsVerified(false);
        setSmtpVerifiedToken('');
        setLastTestResult(null);
    };

    const openTestModal = () => {
        if (!smtpHost.trim()) {
            alert('กรุณากรอก SMTP Host ก่อนทดสอบ');
            return;
        }
        if (!smtpUsername.trim()) {
            alert('กรุณากรอกอีเมลผู้ส่ง (Sender Email / SMTP Username) ก่อนทดสอบ');
            return;
        }
        setTestFeedback(null);
        setShowTestModal(true);
    };

    const handleRunTestEmail = async (e) => {
        e.preventDefault();
        setTesting(true);
        setTestFeedback({ status: 'loading', message: 'กำลังเชื่อมต่อกับเซิร์ฟเวอร์ SMTP และส่งอีเมลทดสอบ... (อาจใช้เวลา 3-5 วินาที)' });

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch(route('settings.test_email'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    smtp_host: smtpHost.trim(),
                    smtp_port: smtpPort.trim(),
                    smtp_username: smtpUsername.trim(),
                    smtp_password: smtpPassword,
                    smtp_encryption: smtpEncryption,
                    smtp_from_address: smtpUsername.trim(),
                    test_email: testEmailTarget.trim(),
                }),
            });

            const data = await response.json();

            if (response.ok && data.success) {
                setIsVerified(true);
                setSmtpVerifiedToken(data.verified_token || '');
                setLastTestResult({ success: true, message: data.message });
                setTestFeedback({ status: 'success', message: data.message });
            } else {
                setIsVerified(false);
                setSmtpVerifiedToken('');
                setLastTestResult({ success: false, message: data.message || 'การเชื่อมต่อ SMTP ล้มเหลว' });
                setTestFeedback({ status: 'error', message: data.message || 'การเชื่อมต่อ SMTP ล้มเหลว' });
            }
        } catch (err) {
            setIsVerified(false);
            setSmtpVerifiedToken('');
            setLastTestResult({ success: false, message: 'เกิดข้อผิดพลาดในการส่งคำขอ: ' + err.message });
            setTestFeedback({ status: 'error', message: 'เกิดข้อผิดพลาด: ' + err.message });
        } finally {
            setTesting(false);
        }
    };

    const handleRunSlaCheck = async () => {
        setRunningSlaCheck(true);
        setSlaCheckFeedback(null);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch(route('settings.run_sla_check'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();
            setSlaCheckFeedback({ status: data.success ? 'success' : 'error', message: data.message, output: data.output });
        } catch (err) {
            setSlaCheckFeedback({ status: 'error', message: 'เกิดข้อผิดพลาดในการเชื่อมต่อเพื่อตรวจสอบ SLA' });
        } finally {
            setRunningSlaCheck(false);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (isConfigured && !isVerified) {
            let errorReason = 'การตั้งค่า Email (SMTP) ยังไม่ผ่านการตรวจสอบความถูกต้อง';
            if (lastTestResult && !lastTestResult.success) {
                errorReason = 'ผลการตรวจสอบการเชื่อมต่อ SMTP ล่าสุด "ไม่ผ่าน" (' + lastTestResult.message + ')';
            }
            alert('⛔ ไม่สามารถบันทึกการตั้งค่าได้!\n\n' + errorReason + '\n\nกรุณากดปุ่ม "ตรวจสอบการตั้งค่า (Test Connection)" ให้ผ่านก่อนบันทึก เพื่อป้องกันไม่ให้ระบบส่งอีเมลล้มเหลว');
            return;
        }

        setSaving(true);

        router.post(route('settings.update'), {
            app_name: appName,
            support_contact: supportContact,
            app_logo: appLogoFile,
            remove_app_logo: removeAppLogo ? 1 : 0,
            app_logo_login: appLogoLoginFile,
            remove_app_logo_login: removeAppLogoLogin ? 1 : 0,
            app_favicon: appFaviconFile,
            remove_app_favicon: removeAppFavicon ? 1 : 0,
            line_channel_access_token: lineAccessToken,
            line_channel_secret: lineSecret,
            smtp_host: smtpHost,
            smtp_port: smtpPort,
            smtp_username: smtpUsername,
            smtp_password: smtpPassword,
            smtp_encryption: smtpEncryption,
            smtp_verified_token: smtpVerifiedToken,
            audit_log_retention_years: auditRetentionYears,
            sla_alert_enabled: slaAlertEnabled ? '1' : '0',
            sla_warning_threshold_minutes: slaWarningThreshold,
        }, {
            forceFormData: true,
            onFinish: () => setSaving(false),
        });
    };

    return (
        <>
            <Head title="ตั้งค่าระบบและการแจ้งเตือน (System Settings)" />

            <div className="py-6 sm:py-8 bg-gray-50 min-h-screen">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h1 className="text-2xl font-black text-gray-900 tracking-tight">
                                ตั้งค่าระบบและการแจ้งเตือน (System Settings)
                            </h1>
                            <p className="text-sm text-gray-500 mt-1">
                                กำหนดค่าแบรนด์องค์กร การเชื่อมต่ออีเมล (SMTP), LINE OA และระบบดูแลรักษา
                            </p>
                        </div>
                    </div>

                    {/* Quick Anchor Navigation */}
                    <div className="flex flex-wrap items-center gap-2 p-1.5 bg-white rounded-2xl shadow-xs border border-slate-200">
                        <button
                            type="button"
                            onClick={() => document.getElementById('section-branding')?.scrollIntoView({ behavior: 'smooth' })}
                            className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-indigo-600 hover:bg-indigo-50/70 transition cursor-pointer"
                        >
                            <span>🎨</span>
                            <span>1. แบรนด์และองค์กร</span>
                        </button>
                        <button
                            type="button"
                            onClick={() => document.getElementById('section-notifications')?.scrollIntoView({ behavior: 'smooth' })}
                            className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-indigo-600 hover:bg-indigo-50/70 transition cursor-pointer"
                        >
                            <span>✉️</span>
                            <span>2. อีเมลและการแจ้งเตือน</span>
                        </button>
                        <button
                            type="button"
                            onClick={() => document.getElementById('section-system')?.scrollIntoView({ behavior: 'smooth' })}
                            className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-indigo-600 hover:bg-indigo-50/70 transition cursor-pointer"
                        >
                            <span>⚙️</span>
                            <span>3. ระบบและการดูแลรักษา</span>
                        </button>
                    </div>

                    {/* Alerts */}
                    {flash?.success && (
                        <div className="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-xs flex items-start gap-3">
                            <svg className="w-5 h-5 text-emerald-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span className="text-sm font-medium leading-5">{flash.success}</span>
                        </div>
                    )}

                    {flash?.error && (
                        <div className="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-xs flex items-start gap-3">
                            <svg className="w-5 h-5 text-rose-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <strong className="font-semibold text-sm block">เกิดข้อผิดพลาด</strong>
                                <span className="text-xs text-rose-700 block mt-0.5 whitespace-pre-line">{flash.error}</span>
                            </div>
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-8">
                        {/* ========================================================= */}
                        {/* CARD 1: ปรับแต่งแบรนด์และองค์กร (Branding & Identity) */}
                        {/* ========================================================= */}
                        <div id="section-branding" className="bg-white rounded-2xl shadow-xs border border-slate-200 p-6 sm:p-8 space-y-6 scroll-mt-20">
                            {/* Card Header */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-200 gap-3">
                                <div className="flex items-center gap-3">
                                    <div className="p-2.5 rounded-xl bg-indigo-600 text-white shadow-xs shrink-0">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 className="text-base font-bold text-slate-900 tracking-tight flex flex-wrap items-center gap-2">
                                            <span>หมวดที่ 1: ปรับแต่งแบรนด์และตราสัญลักษณ์ (Corporate Branding & White-Labeling)</span>
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800 whitespace-nowrap shrink-0">
                                                Commercial Ready
                                            </span>
                                        </h3>
                                        <p className="text-xs text-slate-500 mt-0.5">
                                            กำหนดชื่อระบบ ตราสัญลักษณ์ (Logo), Favicon และข้อมูลติดต่อ เพื่อสะท้อนอัตลักษณ์ขององค์กรคุณ
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Form Inputs Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        ชื่อระบบ / แอปพลิเคชัน (App Name) <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={appName}
                                        onChange={(e) => setAppName(e.target.value)}
                                        placeholder="เช่น IT Service Desk หรือ Helpdesk Pro"
                                        required
                                        className="w-full border-slate-300 rounded-xl shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5 px-3.5 bg-white"
                                    />
                                    <p className="mt-1 text-[11px] text-slate-400">แสดงบนแท็บเบราว์เซอร์ แถบนำทาง (Navbar) และหัวรายงานเอกสาร</p>
                                    {errors.app_name && <p className="mt-1 text-xs text-rose-500">{errors.app_name}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        ข้อมูลติดต่อฝ่ายบริการไอที (Support Contact / Hotline)
                                    </label>
                                    <input
                                        type="text"
                                        value={supportContact}
                                        onChange={(e) => setSupportContact(e.target.value)}
                                        placeholder="เช่น โทร. 02-123-4567 ต่อ 101 หรือ support@company.com"
                                        className="w-full border-slate-300 rounded-xl shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5 px-3.5 bg-white"
                                    />
                                    <p className="mt-1 text-[11px] text-slate-400">แสดงที่หน้าเข้าสู่ระบบ (Login) เพื่อให้ผู้ใช้งานติดต่อสอบถามได้สะดวก</p>
                                    {errors.support_contact && <p className="mt-1 text-xs text-rose-500">{errors.support_contact}</p>}
                                </div>
                            </div>

                            {/* Logo & Asset Uploaders */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-5 pt-4 border-t border-slate-200">
                                {/* 1. Header Logo */}
                                <div className="bg-slate-50/70 rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between">
                                    <div>
                                        <div className="min-h-[52px] mb-3">
                                            <label className="block text-xs font-bold text-slate-800 mb-1">
                                                โลโก้แถบนำทาง (Header Logo)
                                            </label>
                                            <p className="text-[11px] text-slate-400 leading-tight">แนะนำ PNG โปร่งใส แนวนอน (ไม่เกิน 2MB)</p>
                                        </div>
                                        
                                        <div className="h-16 flex items-center justify-center bg-white border border-dashed border-slate-300 rounded-lg p-2 mb-3">
                                            {appLogoPreview && !removeAppLogo ? (
                                                <img src={appLogoPreview} alt="Header Logo Preview" className="max-h-12 max-w-full object-contain" />
                                            ) : (
                                                <div className="flex items-center gap-2 text-slate-400 text-xs">
                                                    <img src="/images/polymate-logo.png" alt="Default Logo" className="max-h-8 max-w-full object-contain opacity-60" />
                                                    <span className="text-[10px]">(ค่าเริ่มต้น)</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <input
                                            type="file"
                                            id="app_logo_input"
                                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                            onChange={(e) => handleLogoChange(e.target.files[0], setAppLogoFile, setAppLogoPreview, setRemoveAppLogo)}
                                            className="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
                                        />
                                        {(appLogoPreview || appLogoFile) && !removeAppLogo && (
                                            <button
                                                type="button"
                                                onClick={() => handleResetLogo(setAppLogoFile, setAppLogoPreview, setRemoveAppLogo)}
                                                className="text-[11px] text-rose-600 hover:text-rose-800 font-semibold block w-full text-right cursor-pointer"
                                            >
                                                ลบและใช้โลโก้เริ่มต้น
                                            </button>
                                        )}
                                        {removeAppLogo && (
                                            <p className="text-[11px] text-amber-600 font-semibold text-right">
                                                * จะคืนค่าเป็นโลโก้เริ่มต้นเมื่อกดบันทึก
                                            </p>
                                        )}
                                        {errors.app_logo && <p className="text-xs text-rose-500">{errors.app_logo}</p>}
                                    </div>
                                </div>

                                {/* 2. Login Page Logo */}
                                <div className="bg-slate-50/70 rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between">
                                    <div>
                                        <div className="min-h-[52px] mb-3">
                                            <label className="block text-xs font-bold text-slate-800 mb-1">
                                                โลโก้หน้าเข้าสู่ระบบ (Login Logo)
                                            </label>
                                            <p className="text-[11px] text-slate-400 leading-tight">หากไม่ระบุ จะใช้โลโก้เดียวกับส่วนหัว</p>
                                        </div>
                                        
                                        <div className="h-16 flex items-center justify-center bg-white border border-dashed border-slate-300 rounded-lg p-2 mb-3">
                                            {appLogoLoginPreview && !removeAppLogoLogin ? (
                                                <img src={appLogoLoginPreview} alt="Login Logo Preview" className="max-h-12 max-w-full object-contain" />
                                            ) : appLogoPreview && !removeAppLogo ? (
                                                <div className="flex items-center gap-2 text-slate-400 text-xs">
                                                    <img src={appLogoPreview} alt="Header Logo fallback" className="max-h-12 max-w-full object-contain opacity-80" />
                                                    <span className="text-[10px]">(ใช้โลโก้ส่วนหัว)</span>
                                                </div>
                                            ) : (
                                                <div className="flex items-center gap-2 text-slate-400 text-xs">
                                                    <img src="/images/polymate-logo.png" alt="Default Logo" className="max-h-10 max-w-full object-contain opacity-60" />
                                                    <span className="text-[10px]">(ค่าเริ่มต้น)</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <input
                                            type="file"
                                            id="app_logo_login_input"
                                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                            onChange={(e) => handleLogoChange(e.target.files[0], setAppLogoLoginFile, setAppLogoLoginPreview, setRemoveAppLogoLogin)}
                                            className="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
                                        />
                                        {(appLogoLoginPreview || appLogoLoginFile) && !removeAppLogoLogin && (
                                            <button
                                                type="button"
                                                onClick={() => handleResetLogo(setAppLogoLoginFile, setAppLogoLoginPreview, setRemoveAppLogoLogin)}
                                                className="text-[11px] text-rose-600 hover:text-rose-800 font-semibold block w-full text-right cursor-pointer"
                                            >
                                                ลบและใช้ค่าเริ่มต้น
                                            </button>
                                        )}
                                        {removeAppLogoLogin && (
                                            <p className="text-[11px] text-amber-600 font-semibold text-right">
                                                * จะคืนค่าเป็นโลโก้เริ่มต้นเมื่อกดบันทึก
                                            </p>
                                        )}
                                        {errors.app_logo_login && <p className="text-xs text-rose-500">{errors.app_logo_login}</p>}
                                    </div>
                                </div>

                                {/* 3. Favicon */}
                                <div className="bg-slate-50/70 rounded-xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between">
                                    <div>
                                        <div className="min-h-[52px] mb-3">
                                            <label className="block text-xs font-bold text-slate-800 mb-1">
                                                ไอคอนเบราว์เซอร์ (Favicon)
                                            </label>
                                            <p className="text-[11px] text-slate-400 leading-tight">ไฟล์ .ico, .png, .jpg, .svg, .webp (ขนาดไม่เกิน 2MB)</p>
                                        </div>
                                        
                                        <div className="h-16 flex items-center justify-center bg-white border border-dashed border-slate-300 rounded-lg p-2 mb-3">
                                            {appFaviconPreview && !removeAppFavicon ? (
                                                <img src={appFaviconPreview} alt="Favicon Preview" className="w-8 h-8 object-contain" />
                                            ) : (
                                                <div className="flex items-center gap-2 text-slate-400 text-xs">
                                                    <img src="/images/favicon.png" alt="Default Favicon" className="w-8 h-8 object-contain opacity-60" />
                                                    <span className="text-[10px]">(ค่าเริ่มต้น)</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <input
                                            type="file"
                                            id="app_favicon_input"
                                            accept=".ico,image/x-icon,image/vnd.microsoft.icon,image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
                                            onChange={(e) => handleLogoChange(e.target.files[0], setAppFaviconFile, setAppFaviconPreview, setRemoveAppFavicon, true)}
                                            className="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
                                        />
                                        {(appFaviconPreview || appFaviconFile) && !removeAppFavicon && (
                                            <button
                                                type="button"
                                                onClick={() => handleResetLogo(setAppFaviconFile, setAppFaviconPreview, setRemoveAppFavicon, true)}
                                                className="text-[11px] text-rose-600 hover:text-rose-800 font-semibold block w-full text-right cursor-pointer"
                                            >
                                                ลบและใช้ค่าเริ่มต้น
                                            </button>
                                        )}
                                        {removeAppFavicon && (
                                            <p className="text-[11px] text-amber-600 font-semibold text-right">
                                                * จะคืนค่าเป็น Favicon เริ่มต้นเมื่อกดบันทึก
                                            </p>
                                        )}
                                        {errors.app_favicon && <p className="text-xs text-rose-500">{errors.app_favicon}</p>}
                                    </div>
                                </div>
                            </div>

                            {/* Live Preview Card */}
                            <div className="bg-slate-50/70 rounded-xl p-4 border border-slate-200 shadow-xs">
                                <div className="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                                    <span>👁️ ตัวอย่างการแสดงผลจำลอง (Live Preview)</span>
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    {/* Navbar Preview */}
                                    <div className="bg-slate-100 p-3 rounded-xl border border-slate-200 flex flex-col justify-between">
                                        <div className="text-[10px] text-slate-500 font-semibold mb-1.5">จำลองแถบนำทาง (Navbar Header)</div>
                                        <div className="bg-white h-12 rounded-lg px-3 flex items-center gap-2.5 shadow-xs border border-slate-100">
                                            <img
                                                src={appLogoPreview && !removeAppLogo ? appLogoPreview : '/images/polymate-logo.png'}
                                                alt="Preview Logo"
                                                className="h-6 w-auto max-w-[100px] object-contain"
                                            />
                                            <div className="flex flex-col min-w-0">
                                                <span className="text-sm font-bold text-indigo-600 truncate leading-tight">
                                                    {appName || 'ITDeskService'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Login Card Preview */}
                                    <div className="bg-slate-100 p-3 rounded-xl border border-slate-200 flex flex-col justify-between">
                                        <div className="text-[10px] text-slate-500 font-semibold mb-1.5">จำลองหน้าเข้าสู่ระบบ (Login Card)</div>
                                        <div className="bg-white p-3 rounded-lg text-center shadow-xs border border-slate-100 space-y-1">
                                            <img
                                                src={appLogoLoginPreview && !removeAppLogoLogin ? appLogoLoginPreview : (appLogoPreview && !removeAppLogo ? appLogoPreview : '/images/polymate-logo.png')}
                                                alt="Preview Login Logo"
                                                className="h-8 w-auto max-w-[120px] object-contain mx-auto"
                                            />
                                            <div className="text-xs font-bold text-gray-800 leading-tight">
                                                {appName || 'ITDeskService'}
                                            </div>
                                            {supportContact && (
                                                <div className="text-[10px] text-slate-400">📞 {supportContact}</div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Browser Tab Preview */}
                                    <div className="bg-slate-100 p-3 rounded-xl border border-slate-200 flex flex-col justify-between">
                                        <div className="text-[10px] text-slate-500 font-semibold mb-1.5">จำลองแท็บเบราว์เซอร์ (Browser Tab & Favicon)</div>
                                        <div className="bg-slate-200/70 h-12 rounded-lg px-2 flex items-end">
                                            <div className="bg-white h-9 px-2.5 rounded-t-lg flex items-center gap-2 shadow-xs border-t border-x border-slate-300 max-w-[180px] w-full">
                                                <img
                                                    src={appFaviconPreview && !removeAppFavicon ? appFaviconPreview : '/images/favicon.png'}
                                                    alt="Preview Favicon"
                                                    className="w-4 h-4 shrink-0 object-contain"
                                                />
                                                <span className="text-xs font-medium text-slate-700 truncate">
                                                    {appName || 'ITDeskService'}
                                                </span>
                                                <span className="text-slate-400 text-[10px] ml-auto shrink-0">✕</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* ========================================================= */}
                        {/* CARD 2: อีเมลและการแจ้งเตือน (Notifications & Integrations) */}
                        {/* ========================================================= */}
                        <div id="section-notifications" className="bg-white rounded-2xl shadow-xs border border-slate-200 p-6 sm:p-8 space-y-8 scroll-mt-20">
                            {/* Card Header */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-200 gap-3">
                                <div className="flex items-center gap-3">
                                    <div className="p-2.5 rounded-xl bg-indigo-50 text-indigo-600 shadow-xs shrink-0">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 className="text-base font-bold text-slate-900 tracking-tight">
                                            หมวดที่ 2: อีเมลและการแจ้งเตือน (Notifications & Integrations)
                                        </h3>
                                        <p className="text-xs text-slate-500 mt-0.5">
                                            จัดการระบบส่งอีเมล (SMTP), ตรวจจับและแจ้งเตือนเวลา SLA ล่วงหน้า และการเชื่อมต่อ LINE Official Account
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* 2.1 Email (SMTP) Settings */}
                            <div className="space-y-4">
                                <div className="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-slate-100">
                                    <div className="flex items-center gap-2.5">
                                        <h4 className="text-sm font-bold text-slate-800 flex items-center gap-2">
                                            <span className="w-2 h-2 rounded-full bg-indigo-500"></span>
                                            2.1 เซิร์ฟเวอร์ส่งอีเมล (Email SMTP Settings)
                                        </h4>
                                        {/* Status Badge */}
                                        {!isConfigured ? (
                                            <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                                <span className="w-1.5 h-1.5 rounded-full bg-gray-400"></span> ยังไม่ได้ตั้งค่า
                                            </span>
                                        ) : isVerified ? (
                                            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> ผ่านการตรวจสอบแล้ว (พร้อมใช้งาน)
                                            </span>
                                        ) : lastTestResult && !lastTestResult.success ? (
                                            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-300">
                                                <span className="w-1.5 h-1.5 rounded-full bg-rose-600"></span> ตรวจสอบไม่ผ่าน (ห้ามบันทึก)
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                                                <span className="w-1.5 h-1.5 rounded-full bg-amber-500"></span> ยังไม่ผ่านการตรวจสอบ (ต้องทดสอบก่อนบันทึก)
                                            </span>
                                        )}
                                    </div>
                                    <p className="text-xs text-slate-500 w-full">การตั้งค่าเซิร์ฟเวอร์ส่งอีเมลสำหรับการแจ้งเตือนและการยืนยันตัวตน (Email Verification)</p>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            SMTP Host <span className="text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={smtpHost}
                                            onChange={handleSmtpChange(setSmtpHost)}
                                            placeholder="เช่น smtp.example.com"
                                            className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-mono leading-normal"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            SMTP Port <span className="text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={smtpPort}
                                            onChange={handlePortChange}
                                            placeholder="เช่น 465 หรือ 587"
                                            className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-mono leading-normal"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            อีเมลผู้ส่ง (Sender Email / SMTP Username) <span className="text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={smtpUsername}
                                            onChange={handleSmtpChange(setSmtpUsername)}
                                            placeholder="เช่น admin@polymate.co.th"
                                            className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-mono leading-normal"
                                            required
                                        />
                                        <p className="mt-1 text-xs text-gray-400">ระบบจะใช้อีเมลนี้เป็นทั้งบัญชีล็อกอินส่งเมล (Username) และอีเมลผู้ส่ง (From Address)</p>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            รหัสผ่าน (SMTP Password)
                                        </label>
                                        <input
                                            type="password"
                                            value={smtpPassword}
                                            onChange={handleSmtpChange(setSmtpPassword)}
                                            placeholder="เว้นว่างหากไม่ต้องการเปลี่ยน"
                                            className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm leading-normal"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            การเข้ารหัสความปลอดภัย (Encryption)
                                        </label>
                                        <select
                                            value={smtpEncryption}
                                            onChange={handleSmtpChange(setSmtpEncryption)}
                                            className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                        >
                                            <option value="ssl">SSL (แนะนำสำหรับพอร์ต 465)</option>
                                            <option value="tls">TLS / STARTTLS (แนะนำสำหรับพอร์ต 587)</option>
                                            <option value="none">None (ไม่เข้ารหัส)</option>
                                        </select>
                                    </div>

                                    <div className="rounded-xl bg-gray-50 border border-gray-200 p-3.5 text-xs text-gray-600 flex flex-col justify-center">
                                        <span className="font-semibold text-gray-700 mb-1 flex items-center gap-1.5">
                                            <svg className="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            คำแนะนำการตั้งค่าพอร์ตและการเข้ารหัส:
                                        </span>
                                        <ul className="list-disc list-inside space-y-0.5 text-gray-500 text-[11px]">
                                            <li><strong>พอร์ต 465:</strong> ใช้ Encryption แบบ <code className="text-indigo-600 font-mono">ssl</code> (แนะนำสำหรับ Mail Server โฮสติ้ง)</li>
                                            <li><strong>พอร์ต 587:</strong> ใช้ Encryption แบบ <code className="text-indigo-600 font-mono">tls</code></li>
                                        </ul>
                                    </div>
                                </div>

                                {/* SMTP Test Connection Box */}
                                <div className="mt-4">
                                    {isConfigured ? (
                                        isVerified ? (
                                            <div className="p-4 bg-emerald-50/90 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <svg className="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span className="text-sm font-bold text-emerald-900">ผลการตรวจสอบ: ผ่าน (Connected Successfully)</span>
                                                    </div>
                                                    <p className="text-xs text-emerald-700 mt-1">{lastTestResult?.message || 'การตั้งค่าปัจจุบันได้รับการตรวจสอบแล้ว สามารถกดบันทึกการตั้งค่าระบบได้ตามปกติ'}</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={openTestModal}
                                                    className="shrink-0 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition cursor-pointer"
                                                >
                                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    <span>ทดสอบซ้ำ</span>
                                                </button>
                                            </div>
                                        ) : lastTestResult && !lastTestResult.success ? (
                                            <div className="p-4 bg-rose-50 border border-rose-300 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
                                                <div className="max-w-xl">
                                                    <div className="flex items-center gap-2">
                                                        <svg className="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span className="text-sm font-bold text-rose-900">ผลการตรวจสอบ: ไม่ผ่าน (Connection Failed)</span>
                                                    </div>
                                                    <p className="text-xs text-rose-700 mt-1 break-words">{lastTestResult.message}</p>
                                                    <p className="text-[11px] font-semibold text-rose-800 mt-1">⚠️ ระบบจะไม่อนุญาตให้บันทึกจนกว่าจะแก้ไขและทดสอบผ่าน</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={openTestModal}
                                                    className="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-sm transition cursor-pointer"
                                                >
                                                    <span>แก้ไขและตรวจสอบใหม่</span>
                                                </button>
                                            </div>
                                        ) : (
                                            <div className="p-4 bg-amber-50/90 border border-amber-300 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <svg className="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        <span className="text-sm font-bold text-amber-900">ต้องตรวจสอบการตั้งค่าก่อนบันทึก</span>
                                                    </div>
                                                    <p className="text-xs text-amber-700 mt-1">กรุณากด "ตรวจสอบการตั้งค่า (Test Connection)" ให้ผ่านก่อนบันทึก เพื่อป้องกันไม่ให้ระบบส่งอีเมลล้มเหลว</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={openTestModal}
                                                    className="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white text-xs font-bold rounded-lg shadow-sm transition cursor-pointer"
                                                >
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>ตรวจสอบการตั้งค่า (Test Connection)</span>
                                                </button>
                                            </div>
                                        )
                                    ) : (
                                        <div className="p-4 bg-gray-50 border border-gray-200 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <svg className="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span className="text-sm font-bold text-gray-700">ยังไม่มีการตั้งค่า Mail Server</span>
                                                </div>
                                                <p className="text-xs text-gray-500 mt-1">กรอกข้อมูล Host, Port, อีเมลผู้ส่ง และ Password เพื่อเริ่มใช้งานระบบส่งอีเมล</p>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={openTestModal}
                                                className="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition cursor-pointer"
                                            >
                                                ตรวจสอบการตั้งค่า (Test Connection)
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="border-t border-slate-200" />

                            {/* 2.2 SLA Overdue Warning Alert */}
                            <div className="space-y-4">
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-slate-100 gap-3">
                                    <div className="flex items-center gap-2.5">
                                        <h4 className="text-sm font-bold text-slate-800 flex items-center gap-2">
                                            <span className="w-2 h-2 rounded-full bg-orange-500"></span>
                                            2.2 การแจ้งเตือนเคสใกล้หมดเวลา SLA (SLA Overdue Warning Alert)
                                        </h4>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={handleRunSlaCheck}
                                        disabled={runningSlaCheck}
                                        className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-orange-50 border border-orange-200 rounded-xl text-xs font-bold text-orange-700 hover:bg-orange-100 transition disabled:opacity-50 shrink-0 cursor-pointer"
                                    >
                                        <svg className={`w-4 h-4 ${runningSlaCheck ? 'animate-spin' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        {runningSlaCheck ? 'กำลังตรวจสอบ...' : 'ตรวจสอบ SLA ทันที (Run Check)'}
                                    </button>
                                </div>
                                <p className="text-xs text-slate-500">ตั้งค่าระบบตรวจสอบและส่งการแจ้งเตือนอัตโนมัติไปยังทีมไอทีเมื่อเคสใกล้หมดเวลา SLA หรือเกินกำหนด</p>

                                {slaCheckFeedback && (
                                    <div className={`p-4 rounded-xl text-xs flex flex-col gap-2 ${
                                        slaCheckFeedback.status === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'
                                    }`}>
                                        <div className="font-bold">{slaCheckFeedback.message}</div>
                                        {slaCheckFeedback.output && (
                                            <pre className="p-3 bg-gray-900 text-gray-100 rounded-lg font-mono text-[11px] overflow-x-auto whitespace-pre-wrap">
                                                {slaCheckFeedback.output}
                                            </pre>
                                        )}
                                    </div>
                                )}

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                                    <div>
                                        <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                            สถานะระบบแจ้งเตือน SLA
                                        </label>
                                        <div className="flex items-center gap-3 mt-1">
                                            <input type="hidden" name="sla_alert_enabled" value={slaAlertEnabled ? '1' : '0'} />
                                            <button
                                                type="button"
                                                onClick={() => setSlaAlertEnabled(!slaAlertEnabled)}
                                                className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                                                    slaAlertEnabled ? 'bg-orange-600' : 'bg-gray-200'
                                                }`}
                                            >
                                                <span
                                                    className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                                                        slaAlertEnabled ? 'translate-x-5' : 'translate-x-0'
                                                    }`}
                                                />
                                            </button>
                                            <span className="text-sm font-semibold text-gray-700">
                                                {slaAlertEnabled ? 'เปิดใช้งานการตรวจสอบและแจ้งเตือนอัตโนมัติ' : 'ปิดการแจ้งเตือนชั่วคราว'}
                                            </span>
                                        </div>
                                        <p className="mt-2 text-xs text-gray-400">
                                            เมื่อเปิดใช้งาน ระบบจะตรวจสอบเคสที่ใกล้หมดเวลาและเคสที่เกินกำหนดผ่าน Scheduled Cron Job ทุก 1 นาที
                                        </p>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                            ระยะเวลาแจ้งเตือนล่วงหน้า (Warning Threshold)
                                        </label>
                                        <select
                                            name="sla_warning_threshold_minutes"
                                            value={slaWarningThreshold}
                                            onChange={(e) => setSlaWarningThreshold(e.target.value)}
                                            className="w-full rounded-xl border-gray-200 focus:border-indigo-400 focus:ring focus:ring-indigo-100 text-sm py-2.5 pl-3.5 pr-10 bg-gray-50/50 cursor-pointer"
                                        >
                                            <option value="15">15 นาที ก่อนถึงกำหนด SLA</option>
                                            <option value="30">30 นาที ก่อนถึงกำหนด SLA</option>
                                            <option value="45">45 นาที ก่อนถึงกำหนด SLA</option>
                                            <option value="60">60 นาที (1 ชั่วโมง) ก่อนถึงกำหนด SLA (แนะนำ)</option>
                                            <option value="120">120 นาที (2 ชั่วโมง) ก่อนถึงกำหนด SLA</option>
                                        </select>
                                        <p className="mt-1 text-xs text-gray-400">
                                            ระบบจะส่งการแจ้งเตือนไปยังช่างผู้รับผิดชอบ, ทีมเฉพาะทาง, Helpdesk และ Manager ตามระยะเวลาที่เลือก
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="border-t border-slate-200" />

                            {/* 2.3 LINE OA Settings */}
                            <div className="space-y-4">
                                <h4 className="text-sm font-bold text-slate-800 flex items-center gap-2 pb-2 border-b border-slate-100">
                                    <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    2.3 LINE Official Account (LINE OA Settings)
                                </h4>
                                <p className="text-xs text-slate-500">เชื่อมต่อกับ LINE Messaging API สำหรับระบบแจ้งเตือนและแชทบอทบริการแจ้งซ่อม</p>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Channel Access Token
                                        </label>
                                        <input
                                            type="text"
                                            value={lineAccessToken}
                                            onChange={(e) => setLineAccessToken(e.target.value)}
                                            className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-mono"
                                            placeholder="กรอก Access Token"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Channel Secret
                                        </label>
                                        <input
                                            type="password"
                                            value={lineSecret}
                                            onChange={(e) => setLineSecret(e.target.value)}
                                            className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-mono"
                                            placeholder="กรอก Channel Secret"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* ========================================================= */}
                        {/* CARD 3: ระบบและการดูแลรักษา (System Health & Maintenance) */}
                        {/* ========================================================= */}
                        <div id="section-system" className="bg-white rounded-2xl shadow-xs border border-slate-200 p-6 sm:p-8 space-y-6 scroll-mt-20">
                            {/* Card Header */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-200 gap-3">
                                <div className="flex items-center gap-3">
                                    <div className="p-2.5 rounded-xl bg-slate-100 text-slate-700 shadow-xs shrink-0">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 className="text-base font-bold text-slate-900 tracking-tight">
                                            หมวดที่ 3: ระบบและการดูแลรักษา (System Health & Maintenance)
                                        </h3>
                                        <p className="text-xs text-slate-500 mt-0.5">
                                            ตรวจสอบสภาพแวดล้อมการทำงานของเซิร์ฟเวอร์ และกำหนดระยะเวลาจัดเก็บประวัติการใช้งาน
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="text-[11px] font-mono text-slate-600 bg-slate-100 px-3 py-1 rounded-lg border border-slate-200 font-semibold">
                                        PHP {diagnostics?.php_version || '8.3'} • Laravel {diagnostics?.laravel_version || '11'}
                                    </span>
                                </div>
                            </div>

                            {/* Diagnostics 3 Cards Grid */}
                            <div>
                                <h4 className="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                                    <span className="w-2 h-2 rounded-full bg-slate-500"></span>
                                    3.1 สถานะสุขภาพของระบบ (System Diagnostics & Health)
                                </h4>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    {/* 1. Scheduler Cron Heartbeat */}
                                    <div className="bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-800 shadow-md hover:shadow-lg hover:border-slate-700 transition-all duration-200 flex flex-col justify-between text-slate-100">
                                        <div>
                                            {/* Top Row: Icon on left, Status Badge on right */}
                                            <div className="flex items-center justify-between gap-2 mb-3">
                                                <div className="w-10 h-10 rounded-xl bg-indigo-950/80 border border-indigo-500/30 flex items-center justify-center text-indigo-400 shrink-0 shadow-inner">
                                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                {diagnostics?.scheduler?.status === 'active' ? (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-500/30 shrink-0">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse shadow-[0_0_6px_rgba(52,211,153,0.8)]" />
                                                        ปกติ
                                                    </span>
                                                ) : diagnostics?.scheduler?.status === 'warning' ? (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-950/80 text-amber-300 border border-amber-500/30 shrink-0">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse shadow-[0_0_6px_rgba(251,191,36,0.8)]" />
                                                        ดีเลย์
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-950/80 text-rose-300 border border-rose-500/30 shrink-0">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-rose-400 shadow-[0_0_6px_rgba(248,113,113,0.8)]" />
                                                        ไม่ทำงาน
                                                    </span>
                                                )}
                                            </div>

                                            {/* Title & Subtitle */}
                                            <div className="mb-3">
                                                <h5 className="text-sm font-bold text-white tracking-wide leading-snug">
                                                    งานอัตโนมัติ (Cron Job)
                                                </h5>
                                                <p className="text-xs text-slate-400 font-medium mt-0.5">
                                                    Laravel Scheduler (ทุก 1 นาที)
                                                </p>
                                            </div>

                                            {/* Metric & Details */}
                                            <div className="space-y-0.5 mb-3">
                                                {diagnostics?.scheduler?.last_heartbeat ? (
                                                    <>
                                                        <div className="text-sm font-bold text-slate-100 leading-snug">
                                                            {diagnostics.scheduler.minutes_ago !== null && diagnostics.scheduler.minutes_ago < 1
                                                                ? 'ทำงานเมื่อสักครู่ (< 1 นาที)'
                                                                : `ทำงานเมื่อ ${Math.round(diagnostics.scheduler.minutes_ago || 1)} นาทีที่แล้ว`}
                                                        </div>
                                                        <div className="text-[11px] text-slate-400 font-mono" title={diagnostics.scheduler.last_heartbeat}>
                                                            ล่าสุด: {diagnostics.scheduler.last_time ? `${diagnostics.scheduler.last_time} น.` : ''} ({diagnostics.scheduler.last_heartbeat.split(' ')[0]} {diagnostics.scheduler.last_heartbeat.split(' ')[1]})
                                                        </div>
                                                    </>
                                                ) : (
                                                    <>
                                                        <div className="text-sm font-bold text-slate-500">ยังไม่มีการรัน Cron</div>
                                                        <div className="text-[11px] text-rose-400">โปรดตั้งค่า Cron บน CloudPanel</div>
                                                    </>
                                                )}
                                            </div>
                                        </div>

                                        <div className="border-t border-slate-800/80 pt-2.5 mt-1">
                                            <p className="text-[11px] text-slate-400 leading-relaxed line-clamp-2">
                                                ตรวจจับแจ้งเตือน SLA, สำรองข้อมูลประจำวัน และเคลียร์ Audit Log
                                            </p>
                                        </div>
                                    </div>

                                    {/* 2. Database Connection */}
                                    <div className="bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-800 shadow-md hover:shadow-lg hover:border-slate-700 transition-all duration-200 flex flex-col justify-between text-slate-100">
                                        <div>
                                            {/* Top Row: Icon on left, Status Badge on right */}
                                            <div className="flex items-center justify-between gap-2 mb-3">
                                                <div className="w-10 h-10 rounded-xl bg-blue-950/80 border border-blue-500/30 flex items-center justify-center text-blue-400 shrink-0 shadow-inner">
                                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                                    </svg>
                                                </div>
                                                {diagnostics?.database?.status === 'connected' ? (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-500/30 shrink-0">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.8)]" />
                                                        เชื่อมต่อแล้ว
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-950/80 text-rose-300 border border-rose-500/30 shrink-0">
                                                        ขาดการเชื่อมต่อ
                                                    </span>
                                                )}
                                            </div>

                                            {/* Title & Subtitle */}
                                            <div className="mb-3">
                                                <h5 className="text-sm font-bold text-white tracking-wide leading-snug">
                                                    ฐานข้อมูล (Database)
                                                </h5>
                                                <p className="text-xs text-slate-400 font-medium mt-0.5">
                                                    Primary Relational Engine
                                                </p>
                                            </div>

                                            {/* Metric & Details */}
                                            <div className="space-y-0.5 mb-3">
                                                <div className="text-sm font-bold text-slate-300 leading-snug flex items-center gap-1.5 flex-wrap">
                                                    <span>ไดรเวอร์:</span>
                                                    <span className="font-black text-blue-400 uppercase tracking-wider">
                                                        {diagnostics?.database?.server || diagnostics?.database?.driver || 'MySQL'}
                                                    </span>
                                                    {diagnostics?.database?.version && (
                                                        <span className="text-[11px] font-mono font-bold text-slate-300 bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700">
                                                            v{diagnostics.database.version}
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="text-[11px] text-slate-400 font-mono">
                                                    สถานะ: การเชื่อมต่อสมบูรณ์ (PDO Active)
                                                </div>
                                            </div>
                                        </div>

                                        <div className="border-t border-slate-800/80 pt-2.5 mt-1">
                                            <p className="text-[11px] text-slate-400 leading-relaxed line-clamp-2">
                                                จัดเก็บข้อมูลใบงานแจ้งซ่อม, บัญชีผู้ใช้งาน และประวัติ Audit Log
                                            </p>
                                        </div>
                                    </div>

                                    {/* 3. Storage Symlink */}
                                    <div className="bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-800 shadow-md hover:shadow-lg hover:border-slate-700 transition-all duration-200 flex flex-col justify-between text-slate-100">
                                        <div>
                                            {/* Top Row: Icon on left, Status Badge on right */}
                                            <div className="flex items-center justify-between gap-2 mb-3">
                                                <div className="w-10 h-10 rounded-xl bg-purple-950/80 border border-purple-500/30 flex items-center justify-center text-purple-400 shrink-0 shadow-inner">
                                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                    </svg>
                                                </div>
                                                {diagnostics?.storage?.linked ? (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-500/30 shrink-0">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.8)]" />
                                                        พร้อมใช้งาน
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-950/80 text-rose-300 border border-rose-500/30 shrink-0">
                                                        ยังไม่เชื่อมโยง
                                                    </span>
                                                )}
                                            </div>

                                            {/* Title & Subtitle */}
                                            <div className="mb-3">
                                                <h5 className="text-sm font-bold text-white tracking-wide leading-snug">
                                                    ระบบไฟล์แนบ (Storage)
                                                </h5>
                                                <p className="text-xs text-slate-400 font-medium mt-0.5">
                                                    Public Symbolic Link
                                                </p>
                                            </div>

                                            {/* Metric & Details */}
                                            <div className="space-y-0.5 mb-3">
                                                <div className="text-sm font-mono font-bold text-purple-300 leading-snug">
                                                    {diagnostics?.storage?.linked ? 'public/storage' : 'storage:link'}
                                                </div>
                                                <div className="text-[11px] text-slate-400 font-mono">
                                                    {diagnostics?.storage?.linked ? 'เชื่อมโยงไปยัง storage/app/public' : 'ต้องรัน php artisan storage:link'}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="border-t border-slate-800/80 pt-2.5 mt-1">
                                            <p className="text-[11px] text-slate-400 leading-relaxed line-clamp-2">
                                                โฟลเดอร์จัดเก็บภาพหลักฐานแจ้งซ่อม, ไฟล์แนบแชท และตราสัญลักษณ์
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="border-t border-slate-200" />

                            {/* 3.2 Data Retention Policy */}
                            <div className="space-y-4">
                                <h4 className="text-sm font-bold text-slate-800 flex items-center gap-2 pb-2 border-b border-slate-100">
                                    <span className="w-2 h-2 rounded-full bg-slate-500"></span>
                                    3.2 นโยบายจัดเก็บข้อมูลระบบ (Data Retention Policy)
                                </h4>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            เก็บประวัติการใช้งาน (Audit Logs) ย้อนหลัง (ปี)
                                        </label>
                                        <select
                                            value={auditRetentionYears}
                                            onChange={(e) => setAuditRetentionYears(e.target.value)}
                                            className="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                        >
                                            <option value="1">1 ปี</option>
                                            <option value="2">2 ปี</option>
                                            <option value="3">3 ปี</option>
                                            <option value="5">5 ปี</option>
                                            <option value="99">เก็บถาวร (99 ปี)</option>
                                        </select>
                                        <p className="mt-1 text-xs text-gray-400">
                                            ระบบจะทำการลบประวัติที่เก่ากว่าจำนวนปีที่ตั้งค่าไว้อัตโนมัติทุกๆ วัน
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* ========================================================= */}
                        {/* STICKY BOTTOM ACTION BAR (Save Button) */}
                        {/* ========================================================= */}
                        <div className="sticky bottom-4 z-30 bg-white/95 backdrop-blur-md rounded-2xl shadow-lg border border-slate-200 p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div className="flex items-center gap-3 text-xs text-slate-600 w-full sm:w-auto">
                                <span className="p-2 rounded-xl bg-indigo-50 text-indigo-600 shrink-0">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <div>
                                    <span className="font-bold text-slate-800 block">การตั้งค่าจะมีผลทันทีทั่วทั้งระบบ</span>
                                    <span className="text-[11px] text-slate-500">กรุณาตรวจสอบการเชื่อมต่อ SMTP ให้เรียบร้อยก่อนบันทึก</span>
                                </div>
                            </div>
                            <div className="flex items-center gap-3 w-full sm:w-auto justify-end">
                                <button
                                    type="submit"
                                    disabled={saving}
                                    className="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-7 py-3 bg-indigo-600 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-wider hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 shadow-md transition disabled:opacity-50 cursor-pointer"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    <span>{saving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่าทั้งหมด'}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {/* Test Connection Modal */}
            {showTestModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                    <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
                        <div className="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" onClick={() => !testing && setShowTestModal(false)} />
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div className="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100 z-10">
                            <form onSubmit={handleRunTestEmail}>
                                <div className="bg-white p-6">
                                    <div className="flex items-start gap-4">
                                        <div className="shrink-0 flex items-center justify-center h-11 w-11 rounded-xl bg-amber-100 text-amber-600">
                                            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div className="w-full">
                                            <h3 className="text-base font-bold text-gray-900">ทดสอบการเชื่อมต่อ Email (SMTP)</h3>
                                            <p className="mt-1 text-xs text-gray-500 leading-relaxed">
                                                ระบบจะนำค่า Host, Port, Username และ Password ที่กรอกอยู่ในหน้าจอส่งอีเมลทดสอบจริงทันที
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-5 space-y-3">
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-700">ส่งอีเมลทดสอบไปยัง:</label>
                                            <input
                                                type="email"
                                                value={testEmailTarget}
                                                onChange={(e) => setTestEmailTarget(e.target.value)}
                                                required
                                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                                placeholder="ระบุอีเมลปลายทาง"
                                            />
                                            <p className="mt-1 text-[11px] text-gray-400">ค่าเริ่มต้นคืออีเมลผู้ดูแลระบบปัจจุบัน</p>
                                        </div>

                                        <div className="p-3 bg-gray-50 rounded-xl border border-gray-200 text-xs text-gray-600 space-y-1">
                                            <div className="flex justify-between">
                                                <span className="text-gray-400">Host:</span>
                                                <span className="font-mono text-gray-800 font-semibold truncate max-w-[200px]">{smtpHost || '-'}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-400">Port:</span>
                                                <span className="font-mono text-gray-800 font-semibold">{smtpPort || '-'}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-400">Encryption:</span>
                                                <span className="font-mono text-gray-800 font-semibold">{smtpEncryption || 'None'}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-400">อีเมลผู้ส่ง:</span>
                                                <span className="font-mono text-gray-800 font-semibold truncate max-w-[200px]">{smtpUsername || '-'}</span>
                                            </div>
                                        </div>

                                        {testFeedback && (
                                            <div className="mt-4">
                                                {testFeedback.status === 'loading' && (
                                                    <div className="p-3.5 bg-blue-50 border border-blue-200 rounded-xl text-blue-800 text-xs flex items-center gap-2.5">
                                                        <svg className="animate-spin h-4 w-4 text-blue-600 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                        </svg>
                                                        <span>{testFeedback.message}</span>
                                                    </div>
                                                )}
                                                {testFeedback.status === 'success' && (
                                                    <div className="p-4 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-800 text-xs flex items-start gap-3">
                                                        <svg className="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <div>
                                                            <div className="font-bold text-sm text-emerald-900">ผลการทดสอบ: ผ่าน (Passed)</div>
                                                            <div className="mt-1 text-emerald-700 leading-relaxed">{testFeedback.message}</div>
                                                            <div className="mt-2 text-xs font-semibold text-emerald-900 bg-emerald-100 px-2.5 py-1 rounded border border-emerald-200 inline-block">
                                                                ✅ สามารถกด "บันทึกการตั้งค่า" ได้แล้ว
                                                            </div>
                                                        </div>
                                                    </div>
                                                )}
                                                {testFeedback.status === 'error' && (
                                                    <div className="p-4 bg-rose-50 border border-rose-300 rounded-xl text-rose-800 text-xs flex items-start gap-3">
                                                        <svg className="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <div>
                                                            <div className="font-bold text-sm text-rose-900">ผลการทดสอบ: ไม่ผ่าน (Failed)</div>
                                                            <div className="mt-1 text-rose-700 leading-relaxed break-words">{testFeedback.message}</div>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="bg-gray-50 px-6 py-3.5 flex items-center justify-end gap-2 border-t border-gray-100">
                                    <button
                                        type="button"
                                        disabled={testing}
                                        onClick={() => setShowTestModal(false)}
                                        className="px-4 py-2 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-100 border border-gray-300 rounded-lg shadow-xs transition cursor-pointer"
                                    >
                                        ปิดหน้าต่าง
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={testing}
                                        className="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-xs font-semibold text-white rounded-lg shadow-sm transition disabled:opacity-50 cursor-pointer"
                                    >
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        <span>{testing ? 'กำลังทดสอบ...' : 'ส่งอีเมลทดสอบ'}</span>
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
