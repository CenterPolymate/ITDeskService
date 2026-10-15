import React, { useState, useEffect, useRef } from 'react';
import { Head, router, Link, usePage } from '@inertiajs/react';
import { sendBrowserNotification } from '@/Utils/webNotification';

export default function SuperadminDashboard({ stats, chartLabels = [], chartData = [], latestBackup = null }) {
    const { auth, flash } = usePage().props;
    const [hoveredSegment, setHoveredSegment] = useState(null);
    const prevStatsRef = useRef(null);

    // Watch for new tickets or SLA breaches during polling
    useEffect(() => {
        if (prevStatsRef.current === null) {
            prevStatsRef.current = stats;
            return;
        }

        if (stats?.total_tickets > prevStatsRef.current?.total_tickets) {
            const diff = stats.total_tickets - prevStatsRef.current.total_tickets;
            sendBrowserNotification(`📥 มีเคสเปิดใหม่ในระบบ (+${diff})`, {
                body: `จำนวนใบงานทั้งหมดในระบบ: ${stats.total_tickets}`,
            });
        } else if (stats?.breached_tickets > prevStatsRef.current?.breached_tickets) {
            const diff = stats.breached_tickets - prevStatsRef.current.breached_tickets;
            sendBrowserNotification(`🚨 มีเคสเกินกำหนดเวลา SLA เพิ่มขึ้น (+${diff})`, {
                body: `จำนวนเคสที่ Breached SLA: ${stats.breached_tickets}`,
            });
        }

        prevStatsRef.current = stats;
    }, [stats]);

    // Auto-polling updates every 15 seconds
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({
                only: ['stats', 'chartData', 'latestBackup'],
                preserveScroll: true,
                preserveState: true,
            });
        }, 15000);

        return () => clearInterval(interval);
    }, []);

    const chartColors = [
        '#64748b', // pending - slate-500
        '#a855f7', // analyzing - purple-500
        '#6366f1', // in_progress - indigo-500
        '#f59e0b', // resolved - amber-500
        '#f97316', // approved - orange-500
        '#10b981', // closed - emerald-500
        '#9ca3af', // cancelled - gray-400
    ];

    const totalTicketsInChart = chartData.reduce((acc, curr) => acc + curr, 0);

    // Calculate SVG donut paths
    const radius = 80;
    const strokeWidth = 28;
    const center = 110;
    const circumference = 2 * Math.PI * radius;

    let accumulatedAngle = 0;
    const slices = chartData.map((val, idx) => {
        const percentage = totalTicketsInChart > 0 ? val / totalTicketsInChart : 0;
        const strokeDasharray = `${percentage * circumference} ${circumference}`;
        const strokeDashoffset = -accumulatedAngle * circumference;
        accumulatedAngle += percentage;

        return {
            label: chartLabels[idx] || `Status ${idx + 1}`,
            value: val,
            color: chartColors[idx % chartColors.length],
            strokeDasharray,
            strokeDashoffset,
            percentage: Math.round(percentage * 100),
        };
    });

    return (
        <>
            <Head title="Administrator Dashboard - ITDeskService" />

            <div className="py-8 bg-gray-50 min-h-screen">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Header */}
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h1 className="text-2xl font-black text-gray-900 tracking-tight">
                                Administrator Dashboard
                            </h1>
                            <p className="text-sm text-gray-500 mt-1">
                                ภาพรวมระบบและสถิติการดำเนินงานทั้งหมดของ ITDeskService
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Live Sync (15s)
                            </span>
                        </div>
                    </div>

                    {/* Flash Success */}
                    {flash?.success && (
                        <div className="bg-emerald-50 border border-emerald-400 text-emerald-800 px-4 py-3 rounded-xl shadow-sm flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <svg className="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span className="text-sm font-medium">{flash.success}</span>
                            </div>
                        </div>
                    )}

                    {/* Quick Stats Row 1 */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {/* IT Users */}
                        <a
                            href={route('users.index')}
                            className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-all duration-200 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-md cursor-pointer group"
                        >
                            <div>
                                <p className="text-sm font-medium text-gray-500 mb-1 group-hover:text-indigo-600 transition-colors">
                                    ผู้ใช้งานทีม IT (IT Staff)
                                </p>
                                <h3 className="text-3xl font-black text-indigo-600">
                                    {Number(stats.total_it_users).toLocaleString()}
                                </h3>
                                <p className="text-xs text-green-600 mt-2 font-medium flex items-center">
                                    <span className="inline-block w-2 h-2 rounded-full bg-green-500 mr-1.5"></span>
                                    Active: {Number(stats.active_it_users).toLocaleString()}
                                </p>
                            </div>
                            <div className="p-4 bg-indigo-50 rounded-xl text-indigo-500 group-hover:bg-indigo-100 group-hover:scale-110 transition-all">
                                <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </a>

                        {/* Normal Users */}
                        <a
                            href={route('normal_users.index')}
                            className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-all duration-200 hover:-translate-y-1 hover:border-blue-300 hover:shadow-md cursor-pointer group"
                        >
                            <div>
                                <p className="text-sm font-medium text-gray-500 mb-1 group-hover:text-blue-600 transition-colors">
                                    ผู้ใช้งานทั่วไป (Users)
                                </p>
                                <h3 className="text-3xl font-black text-blue-600">
                                    {Number(stats.total_normal_users).toLocaleString()}
                                </h3>
                                <p className="text-xs text-green-600 mt-2 font-medium flex items-center">
                                    <span className="inline-block w-2 h-2 rounded-full bg-green-500 mr-1.5"></span>
                                    Active: {Number(stats.active_normal_users).toLocaleString()}
                                </p>
                            </div>
                            <div className="p-4 bg-blue-50 rounded-xl text-blue-500 group-hover:bg-blue-100 group-hover:scale-110 transition-all">
                                <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </a>

                        {/* Companies */}
                        <a
                            href={route('companies.index')}
                            className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-all duration-200 hover:-translate-y-1 hover:border-teal-300 hover:shadow-md cursor-pointer group"
                        >
                            <div>
                                <p className="text-sm font-medium text-gray-500 mb-1 group-hover:text-teal-600 transition-colors">
                                    บริษัทในระบบ (Companies)
                                </p>
                                <h3 className="text-3xl font-black text-teal-600">
                                    {Number(stats.total_companies).toLocaleString()}
                                </h3>
                                <p className="text-xs text-green-600 mt-2 font-medium flex items-center">
                                    <span className="inline-block w-2 h-2 rounded-full bg-green-500 mr-1.5"></span>
                                    Active: {Number(stats.active_companies).toLocaleString()}
                                </p>
                            </div>
                            <div className="p-4 bg-teal-50 rounded-xl text-teal-500 group-hover:bg-teal-100 group-hover:scale-110 transition-all">
                                <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        </a>

                        {/* Backup */}
                        <a
                            href={route('backups.index')}
                            className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-all duration-200 hover:-translate-y-1 hover:border-amber-300 hover:shadow-md cursor-pointer group"
                        >
                            <div>
                                <p className="text-sm font-medium text-gray-500 mb-1 group-hover:text-amber-600 transition-colors">
                                    ระบบสำรองข้อมูล (Backup)
                                </p>
                                {latestBackup ? (
                                    <>
                                        <h3 className="text-base font-black text-gray-900 truncate max-w-[170px]" title={latestBackup.last_modified}>
                                            {latestBackup.last_modified}
                                        </h3>
                                        <p className="text-xs text-green-600 mt-2 font-medium flex items-center">
                                            <span className="relative flex h-2 w-2 mr-1.5">
                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                                <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                            </span>
                                            ขนาด: {latestBackup.size}
                                        </p>
                                    </>
                                ) : (
                                    <>
                                        <h3 className="text-base font-bold text-gray-400">ไม่มีข้อมูล</h3>
                                        <p className="text-xs text-gray-500 mt-2 font-medium flex items-center">
                                            <span className="inline-block w-2 h-2 rounded-full bg-gray-400 mr-1.5"></span>
                                            รอการสำรองข้อมูล
                                        </p>
                                    </>
                                )}
                            </div>
                            <div className="p-4 bg-amber-50 rounded-xl text-amber-500 group-hover:bg-amber-100 group-hover:scale-110 transition-all">
                                <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                            </div>
                        </a>
                    </div>

                    {/* Charts & Analytics Row */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Ticket Status Chart */}
                        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:col-span-2 flex flex-col justify-between">
                            <div className="flex justify-between items-center mb-4">
                                <div>
                                    <h3 className="text-lg font-bold text-gray-800">
                                        ปริมาณใบงานตามสถานะ (Ticket Distribution)
                                    </h3>
                                    <p className="text-xs text-gray-500 mt-0.5">
                                        สัดส่วนของใบงานแต่ละขั้นตอนทั้งหมดในระบบ
                                    </p>
                                </div>
                                <span className="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-700 rounded-lg">
                                    รวม {totalTicketsInChart} ใบงาน
                                </span>
                            </div>

                            {totalTicketsInChart === 0 ? (
                                <div className="flex flex-col items-center justify-center text-gray-400 py-16">
                                    <svg className="w-16 h-16 mb-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p className="text-sm font-semibold">ยังไม่มีข้อมูลใบงานในระบบ</p>
                                    <p className="text-xs text-gray-400 mt-1">เมื่อมีการเปิดเคส กราฟจะแสดงการกระจายของสถานะที่นี่</p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-12 items-center gap-6 py-4">
                                    {/* Donut Visual */}
                                    <div className="md:col-span-6 flex flex-col items-center justify-center relative">
                                        <svg width="220" height="220" className="transform -rotate-90">
                                            {slices.map((slice, i) => (
                                                <circle
                                                    key={i}
                                                    cx={center}
                                                    cy={center}
                                                    r={radius}
                                                    fill="transparent"
                                                    stroke={slice.color}
                                                    strokeWidth={hoveredSegment === i ? strokeWidth + 4 : strokeWidth}
                                                    strokeDasharray={slice.strokeDasharray}
                                                    strokeDashoffset={slice.strokeDashoffset}
                                                    className="transition-all duration-300 cursor-pointer"
                                                    onMouseEnter={() => setHoveredSegment(i)}
                                                    onMouseLeave={() => setHoveredSegment(null)}
                                                />
                                            ))}
                                        </svg>
                                        <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                            {hoveredSegment !== null ? (
                                                <>
                                                    <span className="text-2xl font-black text-gray-800">
                                                        {slices[hoveredSegment].value}
                                                    </span>
                                                    <span className="text-xs font-bold text-gray-500 max-w-[100px] text-center truncate">
                                                        {slices[hoveredSegment].label}
                                                    </span>
                                                    <span className="text-[10px] text-gray-400">
                                                        ({slices[hoveredSegment].percentage}%)
                                                    </span>
                                                </>
                                            ) : (
                                                <>
                                                    <span className="text-3xl font-black text-gray-800">
                                                        {totalTicketsInChart}
                                                    </span>
                                                    <span className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                        TICKETS
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                    </div>

                                    {/* Legend */}
                                    <div className="md:col-span-6 space-y-2">
                                        {slices.map((slice, i) => (
                                            <div
                                                key={i}
                                                onMouseEnter={() => setHoveredSegment(i)}
                                                onMouseLeave={() => setHoveredSegment(null)}
                                                className={`flex items-center justify-between p-2 rounded-xl transition-all cursor-pointer ${
                                                    hoveredSegment === i ? 'bg-gray-100 shadow-sm translate-x-1' : 'hover:bg-gray-50'
                                                }`}
                                            >
                                                <div className="flex items-center gap-2.5 truncate">
                                                    <span
                                                        className="w-3.5 h-3.5 rounded-full shrink-0 shadow-sm"
                                                        style={{ backgroundColor: slice.color }}
                                                    />
                                                    <span className="text-xs font-semibold text-gray-700 truncate">
                                                        {slice.label}
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-2 shrink-0">
                                                    <span className="text-xs font-bold text-gray-900">
                                                        {slice.value}
                                                    </span>
                                                    <span className="text-[10px] font-medium text-gray-400 w-9 text-right">
                                                        {slice.percentage}%
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* System Health & Summary Cards */}
                        <div className="space-y-6 flex flex-col justify-between">
                            {/* Resolution Rate Card */}
                            <div className="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-sm p-6 text-white relative overflow-hidden flex-1 flex flex-col justify-between">
                                <div className="absolute -right-10 -top-10 bg-white opacity-10 w-44 h-44 rounded-full blur-2xl pointer-events-none" />
                                <div>
                                    <div className="flex items-center justify-between relative z-10 mb-2">
                                        <h3 className="text-lg font-bold">อัตราการแก้ไขปัญหา</h3>
                                        <span className="p-2 bg-white/20 rounded-xl">
                                            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </span>
                                    </div>
                                    <p className="text-emerald-100 text-xs relative z-10">Resolution Rate</p>

                                    <div className="flex items-end gap-2 relative z-10 my-4">
                                        <span className="text-5xl font-black tracking-tight">{stats.resolution_rate}</span>
                                        <span className="text-3xl font-bold mb-1">%</span>
                                    </div>
                                </div>

                                <div className="mt-4 pt-4 border-t border-white/20 space-y-2 relative z-10">
                                    <div className="flex justify-between text-xs">
                                        <span className="text-emerald-100">ใบงานทั้งหมด</span>
                                        <span className="font-bold">{Number(stats.total_tickets).toLocaleString()}</span>
                                    </div>
                                    <div className="flex justify-between text-xs">
                                        <span className="text-emerald-100">แก้ไขแล้วเสร็จ</span>
                                        <span className="font-bold">{Number(stats.resolved_tickets).toLocaleString()}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Breached SLA Card */}
                            <div className="bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl shadow-sm p-6 text-white relative overflow-hidden flex-1 flex flex-col justify-between">
                                <div className="absolute -right-10 -top-10 bg-white opacity-10 w-44 h-44 rounded-full blur-2xl pointer-events-none" />
                                <div>
                                    <div className="flex items-center justify-between relative z-10 mb-2">
                                        <h3 className="text-lg font-bold">ซ่อมเสร็จเกิน SLA</h3>
                                        <span className="p-2 bg-white/20 rounded-xl">
                                            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </span>
                                    </div>
                                    <p className="text-rose-100 text-xs relative z-10">Breached SLA Tickets</p>

                                    <div className="flex items-end gap-2 relative z-10 my-4">
                                        <span className="text-5xl font-black tracking-tight">{Number(stats.breached_tickets).toLocaleString()}</span>
                                        <span className="text-lg font-bold mb-1 text-rose-200">ใบ</span>
                                    </div>
                                </div>

                                <div className="mt-4 pt-4 border-t border-white/20 space-y-2 relative z-10">
                                    <div className="flex justify-between text-xs">
                                        <span className="text-rose-100">คิดเป็นสัดส่วน</span>
                                        <span className="font-bold">
                                            {stats.total_tickets > 0 ? Math.round((stats.breached_tickets / stats.total_tickets) * 100) : 0}%
                                        </span>
                                    </div>
                                    <div className="flex justify-between text-xs">
                                        <span className="text-rose-100">สถานะ</span>
                                        <span className="font-bold">
                                            {stats.breached_tickets === 0 ? 'อยู่ในเกณฑ์ยอดเยี่ยม' : 'ต้องเฝ้าระวัง'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </>
    );
}
