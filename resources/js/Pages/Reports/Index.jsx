import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({
    filters = {},
    stats = {},
    fiveMDistribution = [],
    topRootCauses = [],
    teamDistribution = [],
    priorityDistribution = [],
    pcarIncidents = [],
    companies = [],
    thaiMonthNames = {},
    years = [],
    selectedMonthName = '',
    buddhistYear = 0,
}) {
    const [month, setMonth] = useState(filters.month || new Date().getMonth() + 1);
    const [year, setYear] = useState(filters.year || new Date().getFullYear());
    const [company, setCompany] = useState(filters.company || 'all');
    const [team, setTeam] = useState(filters.team || 'all');
    const [hovered5MSlice, setHovered5MSlice] = useState(null);

    // Filter submit handler
    const applyFilter = (newMonth = month, newYear = year, newCompany = company, newTeam = team) => {
        router.get(
            route('reports.index'),
            {
                month: newMonth,
                year: newYear,
                company: newCompany,
                team: newTeam,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Quick filter shortcuts
    const handleThisMonth = () => {
        const now = new Date();
        const currentM = now.getMonth() + 1;
        const currentY = now.getFullYear();
        setMonth(currentM);
        setYear(currentY);
        applyFilter(currentM, currentY, company, team);
    };

    const handleLastMonth = () => {
        const now = new Date();
        let lastM = now.getMonth();
        let lastY = now.getFullYear();
        if (lastM === 0) {
            lastM = 12;
            lastY -= 1;
        }
        setMonth(lastM);
        setYear(lastY);
        applyFilter(lastM, lastY, company, team);
    };

    const handlePrint = () => {
        window.print();
    };

    // 5M Donut Chart Calculation
    const total5MCount = fiveMDistribution.reduce((sum, item) => sum + item.count, 0);
    const radius = 80;
    const strokeWidth = 26;
    const center = 110;
    const circumference = 2 * Math.PI * radius;

    let accumulatedAngle = 0;
    const donutSlices = fiveMDistribution.map((item) => {
        const percentage = total5MCount > 0 ? item.count / total5MCount : 0;
        const strokeDasharray = `${percentage * circumference} ${circumference}`;
        const strokeDashoffset = -accumulatedAngle * circumference;
        accumulatedAngle += percentage;

        return {
            ...item,
            strokeDasharray,
            strokeDashoffset,
            displayPercentage: Math.round(percentage * 100),
        };
    });

    return (
        <>
            <Head title={`รายงานสถิติประจำเดือน ${selectedMonthName} ${buddhistYear} - ITDeskService`} />

            {/* Print-specific style */}
            <style>{`
                @media print {
                    nav, header, .no-print {
                        display: none !important;
                    }
                    body {
                        background-color: #ffffff !important;
                        color: #000000 !important;
                        font-size: 12px !important;
                    }
                    .print-page {
                        padding: 0 !important;
                        margin: 0 !important;
                        max-width: 100% !important;
                    }
                    .print-card {
                        box-shadow: none !important;
                        border: 1px solid #e2e8f0 !important;
                        page-break-inside: avoid;
                    }
                }
            `}</style>

            <div className="py-8 bg-slate-50 min-h-screen print-page">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Page Header */}
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm print-card">
                        <div>
                            <div className="flex items-center gap-2 mb-1">
                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    Monthly Statistical Report
                                </span>
                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    P-CAR 5M Analytics
                                </span>
                            </div>
                            <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                                รายงานสถิติประจำเดือน {selectedMonthName} พ.ศ. {buddhistYear}
                            </h1>
                            <p className="text-xs sm:text-sm text-slate-500 mt-0.5">
                                สรุปผลการปฏิบัติงานไอที ตัวชี้วัด SLA และการวิเคราะห์แนวโน้มสาเหตุรากเหง้าตามมาตรฐาน P-CAR Form 4
                            </p>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex items-center gap-2.5 shrink-0 no-print">
                            <button
                                onClick={handlePrint}
                                className="inline-flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm rounded-xl border border-slate-200 shadow-sm transition active:scale-95"
                                title="พิมพ์รายงานหรือบันทึกเป็น PDF"
                            >
                                <svg className="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>พิมพ์รายงาน (Print)</span>
                            </button>
                        </div>
                    </div>

                    {/* Filter Controls Bar */}
                    <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4 no-print">
                        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div className="flex items-center gap-2 text-slate-800 font-bold text-sm">
                                <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                <span>ตัวกรองช่วงเวลาและข้อมูล</span>
                            </div>

                            {/* Shortcut Buttons */}
                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    onClick={handleThisMonth}
                                    className="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition"
                                >
                                    เดือนนี้
                                </button>
                                <button
                                    type="button"
                                    onClick={handleLastMonth}
                                    className="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition"
                                >
                                    เดือนที่แล้ว
                                </button>
                            </div>
                        </div>

                        {/* Filter Selects Grid */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-1">
                            {/* Month Select */}
                            <div>
                                <label className="block text-xs font-bold text-slate-600 mb-1">
                                    เดือน (Month)
                                </label>
                                <select
                                    value={month}
                                    onChange={(e) => {
                                        const newM = Number(e.target.value);
                                        setMonth(newM);
                                        applyFilter(newM, year, company, team);
                                    }}
                                    className="w-full h-11 py-2 px-3 text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white leading-normal"
                                >
                                    {Object.entries(thaiMonthNames).map(([key, name]) => (
                                        <option key={key} value={key}>
                                            {name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Year Select */}
                            <div>
                                <label className="block text-xs font-bold text-slate-600 mb-1">
                                    ปี (Year)
                                </label>
                                <select
                                    value={year}
                                    onChange={(e) => {
                                        const newY = Number(e.target.value);
                                        setYear(newY);
                                        applyFilter(month, newY, company, team);
                                    }}
                                    className="w-full h-11 py-2 px-3 text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white leading-normal"
                                >
                                    {years.map((y) => (
                                        <option key={y} value={y}>
                                            พ.ศ. {y + 543} ({y})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Company Select */}
                            <div>
                                <label className="block text-xs font-bold text-slate-600 mb-1">
                                    บริษัท (Company)
                                </label>
                                <select
                                    value={company}
                                    onChange={(e) => {
                                        const newC = e.target.value;
                                        setCompany(newC);
                                        applyFilter(month, year, newC, team);
                                    }}
                                    className="w-full h-11 py-2 px-3 text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white leading-normal"
                                >
                                    <option value="all">ทุกบริษัท (All)</option>
                                    {companies.map((c) => (
                                        <option key={c} value={c}>
                                            {c}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Team Select */}
                            <div>
                                <label className="block text-xs font-bold text-slate-600 mb-1">
                                    ทีมที่รับผิดชอบ (Team)
                                </label>
                                <select
                                    value={team}
                                    onChange={(e) => {
                                        const newT = e.target.value;
                                        setTeam(newT);
                                        applyFilter(month, year, company, newT);
                                    }}
                                    className="w-full h-11 py-2 px-3 text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white leading-normal"
                                >
                                    <option value="all">ทุกทีม (All Teams)</option>
                                    <option value="helpdesk">Helpdesk (Tier 1)</option>
                                    <option value="team_hardware">ทีม Hardware</option>
                                    <option value="team_network">ทีม Network</option>
                                    <option value="team_software">ทีม Software</option>
                                </select>
                            </div>

                            {/* Clear/Reset button */}
                            <div className="flex items-end">
                                <button
                                    type="button"
                                    onClick={() => {
                                        const now = new Date();
                                        setMonth(now.getMonth() + 1);
                                        setYear(now.getFullYear());
                                        setCompany('all');
                                        setTeam('all');
                                        applyFilter(now.getMonth() + 1, now.getFullYear(), 'all', 'all');
                                    }}
                                    className="w-full h-11 py-2 px-3 text-sm font-semibold rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 transition flex items-center justify-center gap-1.5"
                                >
                                    <svg className="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>รีเซ็ตค่าเริ่มต้น</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* KPI Cards Grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                        {/* 1. Total Tickets */}
                        <div className="bg-white rounded-2xl p-5 border-t-4 border-t-indigo-500 border-x border-b border-slate-200 shadow-sm print-card">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-bold text-slate-500">ใบงานทั้งหมด</span>
                                <span className="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </span>
                            </div>
                            <h3 className="text-3xl font-black text-slate-900">
                                {Number(stats.total_tickets || 0).toLocaleString()}
                            </h3>
                            <p className="text-xs text-slate-500 mt-2 flex items-center justify-between">
                                <span>กำลังดำเนินการ:</span>
                                <span className="font-bold text-indigo-600">{stats.active_tickets || 0} เคส</span>
                            </p>
                        </div>

                        {/* 2. Resolution Rate */}
                        <div className="bg-white rounded-2xl p-5 border-t-4 border-t-emerald-500 border-x border-b border-slate-200 shadow-sm print-card">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-bold text-slate-500">อัตราการปิดงาน</span>
                                <span className="p-2 bg-emerald-50 text-emerald-600 rounded-xl">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                            </div>
                            <h3 className="text-3xl font-black text-emerald-600">
                                {stats.resolution_rate || 0}%
                            </h3>
                            <p className="text-xs text-slate-500 mt-2 flex items-center justify-between">
                                <span>แก้ไขสำเร็จ:</span>
                                <span className="font-bold text-emerald-600">{stats.resolved_tickets || 0} เคส</span>
                            </p>
                        </div>

                        {/* 3. SLA Compliance */}
                        <div className="bg-white rounded-2xl p-5 border-t-4 border-t-blue-500 border-x border-b border-slate-200 shadow-sm print-card">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-bold text-slate-500">รักษาเวลา SLA</span>
                                <span className="p-2 bg-blue-50 text-blue-600 rounded-xl">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                            </div>
                            <h3 className="text-3xl font-black text-blue-600">
                                {stats.sla_compliance_rate || 0}%
                            </h3>
                            <p className="text-xs text-slate-500 mt-2 flex items-center justify-between">
                                <span>เกินเวลา SLA:</span>
                                <span className={`font-bold ${stats.sla_breached_count > 0 ? 'text-red-600' : 'text-slate-600'}`}>
                                    {stats.sla_breached_count || 0} เคส
                                </span>
                            </p>
                        </div>

                        {/* 4. Average Resolution Time */}
                        <div className="bg-white rounded-2xl p-5 border-t-4 border-t-teal-500 border-x border-b border-slate-200 shadow-sm print-card">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-bold text-slate-500">เวลาซ่อมเฉลี่ย</span>
                                <span className="p-2 bg-teal-50 text-teal-600 rounded-xl">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </span>
                            </div>
                            <h3 className="text-2xl font-black text-slate-800 tracking-tight truncate" title={stats.avg_resolution_formatted}>
                                {stats.avg_resolution_formatted || '-'}
                            </h3>
                            <p className="text-xs text-slate-500 mt-2 flex items-center justify-between">
                                <span>เกณฑ์เป้าหมาย:</span>
                                <span className="font-bold text-slate-600">≤ 4 ชม.</span>
                            </p>
                        </div>

                        {/* 5. P-CAR Task 2 Cases */}
                        <div className="bg-white rounded-2xl p-5 border-t-4 border-t-purple-500 border-x border-b border-slate-200 shadow-sm print-card">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-bold text-slate-500">มาตรการป้องกัน P-CAR</span>
                                <span className="p-2 bg-purple-50 text-purple-600 rounded-xl">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </span>
                            </div>
                            <h3 className="text-3xl font-black text-purple-600">
                                {stats.pcar_total || 0}
                            </h3>
                            <p className="text-xs text-slate-500 mt-2 flex items-center justify-between">
                                <span>ปิดมาตรการแล้ว:</span>
                                <span className="font-bold text-purple-700">{stats.pcar_done || 0} / {stats.pcar_total || 0}</span>
                            </p>
                        </div>

                        {/* 6. CSAT Satisfaction */}
                        <div className="bg-white rounded-2xl p-5 border-t-4 border-t-amber-500 border-x border-b border-slate-200 shadow-sm print-card">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-bold text-slate-500">คะแนนความพึงพอใจ</span>
                                <span className="p-2 bg-amber-50 text-amber-600 rounded-xl">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </span>
                            </div>
                            <h3 className="text-3xl font-black text-amber-500 flex items-center gap-1.5">
                                <span>{stats.avg_csat > 0 ? stats.avg_csat : '-'}</span>
                                {stats.avg_csat > 0 && <span className="text-base font-bold text-slate-400">/ 5</span>}
                            </h3>
                            <p className="text-xs text-slate-500 mt-2 flex items-center justify-between">
                                <span>ผู้ประเมิน:</span>
                                <span className="font-bold text-slate-600">{stats.rated_tickets_count || 0} คน</span>
                            </p>
                        </div>
                    </div>

                    {/* Section: P-CAR 5M Analytics (Main Core Feature) */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">

                        {/* Left Card: 5M Donut Chart & Breakdown */}
                        <div className="lg:col-span-5 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between print-card">
                            <div>
                                <div className="flex justify-between items-start mb-4">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <span className="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                                            <h2 className="text-lg font-black text-slate-900">
                                                สัดส่วนสาเหตุหลัก 5M (P-CAR Category)
                                            </h2>
                                        </div>
                                        <p className="text-xs text-slate-500 mt-0.5">
                                            การกระจายตัวของสาเหตุรากเหง้าตามหมวดหมู่ 5M
                                        </p>
                                    </div>
                                    <span className="text-xs font-bold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg">
                                        รวม {total5MCount} กรณี
                                    </span>
                                </div>

                                {total5MCount === 0 ? (
                                    <div className="flex flex-col items-center justify-center text-slate-400 py-16">
                                        <svg className="w-16 h-16 mb-3 opacity-30 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <p className="text-sm font-bold text-slate-600">ยังไม่มีข้อมูลการวิเคราะห์ P-CAR</p>
                                        <p className="text-xs text-slate-400 mt-1">
                                            ในรอบเดือน {selectedMonthName} ยังไม่มีการระบุสาเหตุ 5M
                                        </p>
                                    </div>
                                ) : (
                                    <div className="py-2">
                                        {/* Donut Chart Visual */}
                                        <div className="flex flex-col items-center justify-center relative my-2">
                                            <svg width="220" height="220" className="transform -rotate-90">
                                                {donutSlices.map((slice, i) => (
                                                    <circle
                                                        key={slice.prefix}
                                                        cx={center}
                                                        cy={center}
                                                        r={radius}
                                                        fill="transparent"
                                                        stroke={slice.color}
                                                        strokeWidth={hovered5MSlice === i ? strokeWidth + 6 : strokeWidth}
                                                        strokeDasharray={slice.strokeDasharray}
                                                        strokeDashoffset={slice.strokeDashoffset}
                                                        className="transition-all duration-300 cursor-pointer"
                                                        onMouseEnter={() => setHovered5MSlice(i)}
                                                        onMouseLeave={() => setHovered5MSlice(null)}
                                                    />
                                                ))}
                                            </svg>
                                            <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                                {hovered5MSlice !== null ? (
                                                    <>
                                                        <span className="text-2xl font-black text-slate-800">
                                                            {donutSlices[hovered5MSlice].count}
                                                        </span>
                                                        <span className="text-xs font-bold text-slate-500 max-w-[120px] text-center truncate">
                                                            {donutSlices[hovered5MSlice].name}
                                                        </span>
                                                        <span className="text-[11px] font-bold text-slate-400">
                                                            ({donutSlices[hovered5MSlice].displayPercentage}%)
                                                        </span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <span className="text-3xl font-black text-slate-800">
                                                            {total5MCount}
                                                        </span>
                                                        <span className="text-[11px] font-bold text-slate-400 tracking-wider">
                                                            5M CAUSES
                                                        </span>
                                                    </>
                                                )}
                                            </div>
                                        </div>

                                        {/* 5M Legend List */}
                                        <div className="space-y-2 mt-4 pt-3 border-t border-slate-100">
                                            {donutSlices.map((slice, i) => (
                                                <div
                                                    key={slice.prefix}
                                                    onMouseEnter={() => setHovered5MSlice(i)}
                                                    onMouseLeave={() => setHovered5MSlice(null)}
                                                    className={`flex items-center justify-between p-2 rounded-xl transition-all cursor-pointer ${
                                                        hovered5MSlice === i ? 'bg-slate-100 shadow-sm translate-x-1' : 'hover:bg-slate-50'
                                                    }`}
                                                >
                                                    <div className="flex items-center gap-2.5">
                                                        <span
                                                            className="w-3.5 h-3.5 rounded-full shrink-0 shadow-sm"
                                                            style={{ backgroundColor: slice.color }}
                                                        />
                                                        <span className="text-xs font-bold text-slate-700">
                                                            {slice.name}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-xs font-bold text-slate-900">
                                                            {slice.count} เคส
                                                        </span>
                                                        <span className="text-xs font-semibold text-slate-400 w-10 text-right">
                                                            ({slice.displayPercentage}%)
                                                        </span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Right Card: Top Root Causes Horizontal Bar Chart */}
                        <div className="lg:col-span-7 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between print-card">
                            <div>
                                <div className="flex justify-between items-start mb-4">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <span className="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                            <h2 className="text-lg font-black text-slate-900">
                                                จัดอันดับสาเหตุรากเหง้าที่พบบ่อย (Top Root Causes)
                                            </h2>
                                        </div>
                                        <p className="text-xs text-slate-500 mt-0.5">
                                            จำแนกรายละเอียดย่อย 11 ข้อตาม P-CAR Form 4 เรียงลำดับจากความถี่สูงสุด
                                        </p>
                                    </div>
                                </div>

                                {topRootCauses.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center text-slate-400 py-20">
                                        <svg className="w-16 h-16 mb-3 opacity-30 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p className="text-sm font-bold text-slate-600">ไม่มีข้อมูลสาเหตุรากเหง้า</p>
                                        <p className="text-xs text-slate-400 mt-1">ยังไม่มีการบันทึกสาเหตุในรอบเดือนนี้</p>
                                    </div>
                                ) : (
                                    <div className="space-y-3.5 my-2">
                                        {topRootCauses.slice(0, 8).map((cause, index) => {
                                            // Determine color badge based on 5M prefix
                                            let barColor = 'bg-indigo-500';
                                            if (cause.name.startsWith('คน')) barColor = 'bg-blue-500';
                                            else if (cause.name.startsWith('เครื่องจักร')) barColor = 'bg-amber-500';
                                            else if (cause.name.startsWith('วัสดุ')) barColor = 'bg-purple-500';
                                            else if (cause.name.startsWith('วิธีการ')) barColor = 'bg-emerald-500';
                                            else if (cause.name.startsWith('สิ่งแวดล้อม')) barColor = 'bg-cyan-500';

                                            return (
                                                <div key={cause.name} className="space-y-1">
                                                    <div className="flex justify-between items-center text-xs">
                                                        <div className="flex items-center gap-2">
                                                            <span className="w-5 h-5 flex items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600">
                                                                {index + 1}
                                                            </span>
                                                            <span className="font-bold text-slate-800">
                                                                {cause.name}
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-bold text-slate-900">
                                                                {cause.count} ครั้ง
                                                            </span>
                                                            <span className="text-slate-400 font-semibold w-12 text-right">
                                                                ({cause.percentage}%)
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                                        <div
                                                            className={`h-2.5 rounded-full transition-all duration-500 ${barColor}`}
                                                            style={{ width: `${Math.max(cause.percentage, 4)}%` }}
                                                        />
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>

                            {/* Preventive Note Footer */}
                            <div className="mt-4 p-3.5 bg-slate-50 rounded-2xl border border-slate-200/60 text-xs text-slate-600 flex items-start gap-2">
                                <svg className="w-4 h-4 text-indigo-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>
                                    <strong>ข้อสังเกต:</strong> สาเหตุที่มีสัดส่วนสูงสุดควรถูกนำไปกำหนดเป็นมาตรการป้องกันเชิงรุก (Systemic Preventive Actions) และอบรมพนักงานหรือปรับปรุงกระบวนการทำงานเพื่อลดการเกิดซ้ำ
                                </span>
                            </div>
                        </div>

                    </div>

                    {/* Secondary Analytics: Distribution by Team & Priority */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Team Distribution */}
                        <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm print-card">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-base font-black text-slate-900 flex items-center gap-2">
                                    <span className="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                    <span>ปริมาณงานตามทีมเฉพาะทาง (By Team)</span>
                                </h3>
                                <span className="text-xs text-slate-400 font-semibold">Tier 1 & Tier 2</span>
                            </div>
                            <div className="space-y-3">
                                {teamDistribution.map((t) => {
                                    const totalT = stats.total_tickets || 1;
                                    const pct = Math.round((t.count / totalT) * 100);
                                    return (
                                        <div key={t.label} className="space-y-1">
                                            <div className="flex justify-between items-center text-xs">
                                                <span className="font-bold text-slate-700">{t.label}</span>
                                                <span className="font-bold text-slate-900">{t.count} เคส ({pct}%)</span>
                                            </div>
                                            <div className="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div
                                                    className="h-2 rounded-full"
                                                    style={{ width: `${pct}%`, backgroundColor: t.color }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Priority Distribution */}
                        <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm print-card">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-base font-black text-slate-900 flex items-center gap-2">
                                    <span className="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                    <span>ระดับความเร่งด่วนของปัญหา (By Priority)</span>
                                </h3>
                                <span className="text-xs text-slate-400 font-semibold">Impact Assessment</span>
                            </div>
                            <div className="space-y-3">
                                {priorityDistribution.map((p) => {
                                    const totalT = stats.total_tickets || 1;
                                    const pct = Math.round((p.count / totalT) * 100);
                                    return (
                                        <div key={p.label} className="space-y-1">
                                            <div className="flex justify-between items-center text-xs">
                                                <span className="font-bold text-slate-700">{p.label}</span>
                                                <span className="font-bold text-slate-900">{p.count} เคส ({pct}%)</span>
                                            </div>
                                            <div className="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div
                                                    className="h-2 rounded-full"
                                                    style={{ width: `${pct}%`, backgroundColor: p.color }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    {/* Table: P-CAR Incident Cases List */}
                    <div className="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden print-card">
                        <div className="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                            <div>
                                <h3 className="text-lg font-black text-slate-900 flex items-center gap-2">
                                    <span>รายการใบงานมาตรการป้องกัน (P-CAR Incident List)</span>
                                    <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800">
                                        {pcarIncidents.length} เคส
                                    </span>
                                </h3>
                                <p className="text-xs text-slate-500 mt-0.5">
                                    ใบงานที่ได้รับการวิเคราะห์สาเหตุรากเหง้าและวางมาตรการป้องกันในรอบเดือนนี้
                                </p>
                            </div>
                        </div>

                        {pcarIncidents.length === 0 ? (
                            <div className="text-center py-16 text-slate-400">
                                <svg className="w-12 h-12 mx-auto mb-2 opacity-30 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p className="text-sm font-bold text-slate-600">ไม่พบรายการใบงาน P-CAR ในเดือนนี้</p>
                                <p className="text-xs text-slate-400 mt-1">สามารถเลือกเดือนอื่นเพื่อตรวจสอบข้อมูลย้อนหลัง</p>
                            </div>
                        ) : (
                            <>
                                {/* Desktop Table View */}
                                <div className="hidden md:block overflow-x-auto">
                                    <table className="w-full text-left text-xs">
                                        <thead className="bg-slate-50/80 text-slate-600 uppercase font-black tracking-wider border-b border-slate-100">
                                            <tr>
                                                <th className="py-3.5 px-5">Ticket / PCAR No</th>
                                                <th className="py-3.5 px-4">หัวข้อปัญหา</th>
                                                <th className="py-3.5 px-4">บริษัท / แผนก</th>
                                                <th className="py-3.5 px-4">หมวดหมู่ 5M</th>
                                                <th className="py-3.5 px-4">สถานะมาตรการ</th>
                                                <th className="py-3.5 px-4">วันที่เปิด</th>
                                                <th className="py-3.5 px-5 text-right no-print">การจัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {pcarIncidents.map((ticket) => (
                                                <tr key={ticket.id} className="hover:bg-slate-50/60 transition">
                                                    <td className="py-4 px-5">
                                                        <div className="font-bold text-indigo-600 font-mono">
                                                            {ticket.ticket_no}
                                                        </div>
                                                        {ticket.pcar_no && (
                                                            <div className="text-[11px] text-purple-700 font-mono mt-0.5 font-bold">
                                                                {ticket.pcar_no}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="py-4 px-4 max-w-xs">
                                                        <div className="font-bold text-slate-800 line-clamp-1">
                                                            {ticket.title}
                                                        </div>
                                                        {ticket.root_cause_detail && (
                                                            <div className="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                                                สาเหตุ: {ticket.root_cause_detail}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="py-4 px-4">
                                                        <div className="font-semibold text-slate-700">{ticket.company || '-'}</div>
                                                        <div className="text-[11px] text-slate-400">{ticket.department || '-'}</div>
                                                    </td>
                                                    <td className="py-4 px-4">
                                                        {ticket.root_cause_category ? (
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                                {ticket.root_cause_category}
                                                            </span>
                                                        ) : (
                                                            <span className="text-slate-400 italic">-</span>
                                                        )}
                                                    </td>
                                                    <td className="py-4 px-4">
                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${ticket.task2_status_color || 'bg-slate-100 text-slate-700'}`}>
                                                            {ticket.task2_status_label || 'รอดำเนินการ'}
                                                        </span>
                                                    </td>
                                                    <td className="py-4 px-4 text-slate-500 font-medium">
                                                        {ticket.created_at_formatted}
                                                    </td>
                                                    <td className="py-4 px-5 text-right no-print">
                                                        <Link
                                                            href={route('tickets.show', ticket.id)}
                                                            className="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-xl bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-600 transition"
                                                        >
                                                            <span>ดูรายละเอียด</span>
                                                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Mobile Cards View */}
                                <div className="md:hidden divide-y divide-slate-100">
                                    {pcarIncidents.map((ticket) => (
                                        <div key={ticket.id} className="p-4 space-y-2">
                                            <div className="flex justify-between items-start">
                                                <div>
                                                    <div className="font-bold text-indigo-600 text-xs font-mono">
                                                        {ticket.ticket_no}
                                                    </div>
                                                    {ticket.pcar_no && (
                                                        <div className="text-[11px] text-purple-700 font-mono font-bold">
                                                            {ticket.pcar_no}
                                                        </div>
                                                    )}
                                                </div>
                                                <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ${ticket.task2_status_color || 'bg-slate-100 text-slate-700'}`}>
                                                    {ticket.task2_status_label || 'รอดำเนินการ'}
                                                </span>
                                            </div>

                                            <div className="font-bold text-slate-800 text-sm">
                                                {ticket.title}
                                            </div>

                                            {ticket.root_cause_category && (
                                                <div className="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                    {ticket.root_cause_category}
                                                </div>
                                            )}

                                            <div className="flex items-center justify-between text-xs text-slate-500 pt-1">
                                                <span>{ticket.company} - {ticket.department}</span>
                                                <Link
                                                    href={route('tickets.show', ticket.id)}
                                                    className="font-bold text-indigo-600 hover:underline"
                                                >
                                                    ดูใบงาน →
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </>
                        )}
                    </div>

                    {/* Report Footer Note */}
                    <div className="text-center text-xs text-slate-400 py-4 print-card">
                        <p>
                            ระบบ ITDeskService — รายงานสถิติและวิเคราะห์สาเหตุปัญหา P-CAR ประจำเดือน {selectedMonthName} พ.ศ. {buddhistYear}
                        </p>
                    </div>

                </div>
            </div>
        </>
    );
}
