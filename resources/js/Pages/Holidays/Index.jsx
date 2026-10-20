import React, { useState, useEffect } from 'react';

import { Head, useForm, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';

export default function Index({ holidays, suggestedHolidays, fetchYear }) {
    const { data, setData, post, processing } = useForm({
        holidays: []
    });

    const currentYear = new Date().getFullYear();
    const [selectedYear, setSelectedYear] = useState(fetchYear || currentYear);
    const [tableYear, setTableYear] = useState(currentYear.toString());
    const [selectedSuggested, setSelectedSuggested] = useState([]);

    const manualForm = useForm({
        name: '',
        date: ''
    });

    const fetchCalendar = (e) => {
        e.preventDefault();
        router.get(route('holidays.index'), { fetch_year: selectedYear });
    };

    const toggleHoliday = (holiday) => {
        const isSelected = selectedSuggested.some(h => h.date === holiday.date);
        if (isSelected) {
            setSelectedSuggested(selectedSuggested.filter(h => h.date !== holiday.date));
        } else {
            setSelectedSuggested([...selectedSuggested, holiday]);
        }
    };

    useEffect(() => {
        setData('holidays', selectedSuggested);
    }, [selectedSuggested]);

    const submitBulk = (e) => {
        e.preventDefault();
        post(route('holidays.storeBulk'), {
            onSuccess: () => {
                setSelectedSuggested([]);
                // Reload without fetch_year to just show DB
                router.get(route('holidays.index'));
            }
        });
    };

    const submitManual = (e) => {
        e.preventDefault();
        manualForm.post(route('holidays.store'), {
            onSuccess: () => {
                manualForm.reset();
                router.get(route('holidays.index'));
            }
        });
    };

    const monthNames = [
        "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
        "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];

    const daysOfWeek = ["อา", "จ", "อ", "พ", "พฤ", "ศ", "ส"];

    // Format YYYY-MM-DD
    const formatDate = (year, month, day) => {
        const m = (month + 1).toString().padStart(2, '0');
        const d = day.toString().padStart(2, '0');
        return `${year}-${m}-${d}`;
    };

    // Render Calendar
    const renderCalendar = () => {
        const year = parseInt(selectedYear);
        const months = [];

        for (let month = 0; month < 12; month++) {
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            const weeks = [];
            let currentWeek = [];

            // Padding start
            for (let i = 0; i < firstDay; i++) {
                currentWeek.push(null);
            }

            for (let day = 1; day <= daysInMonth; day++) {
                currentWeek.push(day);
                if (currentWeek.length === 7) {
                    weeks.push(currentWeek);
                    currentWeek = [];
                }
            }
            if (currentWeek.length > 0) {
                while (currentWeek.length < 7) {
                    currentWeek.push(null);
                }
                weeks.push(currentWeek);
            }

            months.push({ monthIndex: month, weeks });
        }

        return (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                {months.map(({ monthIndex, weeks }) => (
                    <div key={monthIndex} className="bg-white border rounded-lg p-4 shadow-sm">
                        <h4 className="font-bold text-gray-800 text-center mb-4">{monthNames[monthIndex]}</h4>
                        <div className="grid grid-cols-7 gap-1 text-center mb-2">
                            {daysOfWeek.map(d => (
                                <div key={d} className="text-xs font-semibold text-gray-500">{d}</div>
                            ))}
                        </div>
                        <div>
                            {weeks.map((week, wIndex) => (
                                <div key={wIndex} className="grid grid-cols-7 gap-1 mb-1">
                                    {week.map((day, dIndex) => {
                                        if (!day) return <div key={dIndex} className="h-8 w-8"></div>;

                                        const dateStr = formatDate(year, monthIndex, day);

                                        // Check existing in DB
                                        const existing = holidays?.find(h => {
                                            if (!h?.date) return false;
                                            const d = new Date(h.date);
                                            const dbDate = formatDate(d.getFullYear(), d.getMonth(), d.getDate());
                                            return dbDate === dateStr;
                                        });

                                        // Check suggested
                                        const suggested = suggestedHolidays?.find(h => h?.date === dateStr);

                                        // Check if date is in the past
                                        const today = new Date();
                                        const todayStr = formatDate(today.getFullYear(), today.getMonth(), today.getDate());
                                        const isDatePast = dateStr < todayStr;

                                        // Check if user selected the suggested one
                                        const isSelected = selectedSuggested.some(h => h.date === dateStr);

                                        let bgColor = "hover:bg-gray-100";
                                        let textColor = "text-gray-700";
                                        let tooltip = "";
                                        let dot = null;
                                        let onClick = undefined;
                                        let cursor = "cursor-default";

                                        if (existing) {
                                            textColor = "text-red-600 font-bold";
                                            tooltip = `วันหยุดระบบ: ${existing.name}`;
                                            dot = <div className="w-1.5 h-1.5 bg-red-500 rounded-full mx-auto mt-0.5"></div>;
                                        } else if (suggested) {
                                            if (isDatePast) {
                                                cursor = "cursor-not-allowed opacity-50";
                                                textColor = "text-gray-400 line-through";
                                                tooltip = `(วันหยุดนี้ผ่านมาแล้ว ไม่สามารถเพิ่มย้อนหลังได้) ${suggested.name}`;
                                                onClick = undefined;
                                            } else {
                                                cursor = "cursor-pointer hover:bg-yellow-50";
                                                if (isSelected) {
                                                    textColor = "text-red-600 font-bold";
                                                    bgColor = "bg-red-50 border border-red-200";
                                                    tooltip = `(เตรียมบันทึก) ${suggested.name}`;
                                                    dot = <div className="w-1.5 h-1.5 bg-red-500 rounded-full mx-auto mt-0.5"></div>;
                                                } else {
                                                    textColor = "text-yellow-600 font-bold";
                                                    bgColor = "bg-yellow-50 border border-yellow-200";
                                                    tooltip = `คลิกเพื่อเพิ่ม: ${suggested.name}`;
                                                    dot = <div className="w-1.5 h-1.5 bg-yellow-400 rounded-full mx-auto mt-0.5"></div>;
                                                }
                                                onClick = () => toggleHoliday(suggested);
                                            }
                                        }

                                        return (
                                            <div
                                                key={dIndex}
                                                className={`h-8 w-8 mx-auto flex flex-col justify-center items-center rounded-full transition-colors ${bgColor} ${cursor}`}
                                                title={tooltip}
                                                onClick={onClick}
                                            >
                                                <span className={`text-sm ${textColor}`}>{day}</span>
                                                {dot}
                                            </div>
                                        );
                                    })}
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        );
    };

    const todayDate = new Date();
    const minDateStr = formatDate(todayDate.getFullYear(), todayDate.getMonth(), todayDate.getDate());

    return (
        <>
            <Head title="ตั้งค่าวันหยุดนักขัตฤกษ์" />

            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">ตั้งค่าวันหยุดนักขัตฤกษ์</h2>
                </div>
            </header>

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    <div className="bg-white p-6 rounded-lg shadow-sm">
                        <div className="flex flex-col md:flex-row justify-between items-center mb-6 border-b pb-4">
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">ปฏิทินวันหยุด (ปี {selectedYear})</h3>
                                <p className="text-sm text-gray-500 mt-1">
                                    <span className="inline-block w-2 h-2 bg-red-500 rounded-full mr-1"></span> สีแดง = วันหยุดในระบบ |
                                    <span className="inline-block w-2 h-2 bg-yellow-400 rounded-full mx-1"></span> สีเหลือง = ข้อมูลแนะนำ (คลิกเพื่อเลือก/ยกเลิกการเลือก)
                                </p>
                            </div>

                            <form onSubmit={fetchCalendar} className="flex gap-2 items-end mt-4 md:mt-0">
                                <div>
                                    <InputLabel htmlFor="fetchYear" value="ปี ค.ศ." />
                                    <select
                                        id="fetchYear"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        value={selectedYear}
                                        onChange={(e) => setSelectedYear(e.target.value)}
                                    >
                                        {[...Array(5)].map((_, i) => {
                                            const y = new Date().getFullYear() - 1 + i;
                                            return <option key={y} value={y}>{y}</option>;
                                        })}
                                    </select>
                                </div>
                                <PrimaryButton type="submit">ดึงข้อมูล Google Calendar</PrimaryButton>
                            </form>
                        </div>

                        {renderCalendar()}

                        {selectedSuggested.length > 0 && (
                            <div className="mt-8 bg-indigo-50 p-6 rounded-lg border border-indigo-100 flex flex-col md:flex-row justify-between items-center">
                                <div>
                                    <h4 className="text-lg font-bold text-indigo-900">ยืนยันการเพิ่มวันหยุด</h4>
                                    <p className="text-sm text-indigo-700 mt-1">คุณได้เลือกวันหยุดใหม่จำนวน {selectedSuggested.length} วัน เพื่อบันทึกเข้าสู่ระบบ</p>
                                </div>
                                <form onSubmit={submitBulk} className="mt-4 md:mt-0">
                                    <PrimaryButton disabled={processing} className="bg-green-600 hover:bg-green-700">
                                        ยืนยันบันทึกเป็นวันหยุด
                                    </PrimaryButton>
                                </form>
                            </div>
                        )}
                    </div>

                    <div className="bg-white p-6 rounded-lg shadow-sm">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">เพิ่มวันหยุดแบบกำหนดเอง (Manual)</h3>
                        <form onSubmit={submitManual} className="flex flex-col md:flex-row gap-4 items-end">
                            <div className="w-full md:w-1/3">
                                <InputLabel htmlFor="manualDate" value="วันที่" />
                                <TextInput
                                    id="manualDate"
                                    type="date"
                                    min={minDateStr}
                                    className="mt-1 block w-full"
                                    value={manualForm.data.date}
                                    onChange={(e) => manualForm.setData('date', e.target.value)}
                                    required
                                />
                                <p className="text-xs text-gray-400 mt-1">ไม่อนุญาตให้เลือกวันหยุดย้อนหลัง เพื่อป้องกันผลกระทบต่อ SLA</p>
                                <InputError message={manualForm.errors.date} className="mt-2" />
                            </div>
                            <div className="w-full md:w-1/2">
                                <InputLabel htmlFor="manualName" value="ชื่อวันหยุด" />
                                <TextInput
                                    id="manualName"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={manualForm.data.name}
                                    onChange={(e) => manualForm.setData('name', e.target.value)}
                                    required
                                    placeholder="เช่น วันหยุดพิเศษบริษัท"
                                />
                                <InputError message={manualForm.errors.name} className="mt-2" />
                            </div>
                            <div>
                                <PrimaryButton disabled={manualForm.processing}>บันทึก</PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <div className="bg-white p-6 rounded-lg shadow-sm">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">รายการวันหยุดในระบบ</h3>
                                <p className="text-xs text-gray-500 mt-0.5">
                                    แสดงเฉพาะวันหยุดประจำปี {tableYear === 'all' ? 'ทั้งหมด' : parseInt(tableYear) + 543} ({tableYear === 'all' ? 'All' : tableYear})
                                </p>
                            </div>

                            {/* Year Filter Controls */}
                            <div className="flex items-center gap-2">
                                <label htmlFor="tableYearFilter" className="text-xs font-semibold text-gray-600 whitespace-nowrap">
                                    เลือกปี:
                                </label>
                                <select
                                    id="tableYearFilter"
                                    value={tableYear}
                                    onChange={(e) => setTableYear(e.target.value)}
                                    className="text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5 pl-3 pr-8"
                                >
                                    {/* Generate available years list */}
                                    {(() => {
                                        const yearSet = new Set([currentYear, currentYear - 1, currentYear + 1]);
                                        (holidays || []).forEach(h => {
                                            if (h?.date) {
                                                const y = new Date(h.date).getFullYear();
                                                if (!isNaN(y)) yearSet.add(y);
                                            }
                                        });
                                        const sortedYears = Array.from(yearSet).sort((a, b) => b - a);

                                        return (
                                            <>
                                                {sortedYears.map(yr => (
                                                    <option key={yr} value={yr.toString()}>
                                                        ปี {yr + 543} ({yr}) {yr === currentYear ? '— ปีปัจจุบัน' : ''}
                                                    </option>
                                                ))}
                                                <option value="all">แสดงทั้งหมดทุกปี</option>
                                            </>
                                        );
                                    })()}
                                </select>
                            </div>
                        </div>

                        {(() => {
                            const filteredHolidays = (holidays || []).filter(h => {
                                if (tableYear === 'all') return true;
                                if (!h?.date) return false;
                                const y = new Date(h.date).getFullYear();
                                return y.toString() === tableYear;
                            });

                            return filteredHolidays.length > 0 ? (
                                <div className="overflow-x-auto rounded-lg border border-gray-200">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">วันที่</th>
                                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ชื่อวันหยุด</th>
                                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">สถานะ</th>
                                                <th className="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {filteredHolidays.map(holiday => {
                                                const today = new Date();
                                                today.setHours(0, 0, 0, 0);
                                                const hDate = holiday?.date ? new Date(holiday.date) : null;
                                                if (hDate) {
                                                    hDate.setHours(0, 0, 0, 0);
                                                }
                                                const isPast = hDate && hDate < today;

                                                return (
                                                    <tr key={holiday.id} className={`transition-colors ${isPast ? 'bg-gray-50/40 hover:bg-gray-50' : 'hover:bg-blue-50/20'}`}>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            {holiday?.date ? new Date(holiday.date).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : '-'}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                                            {holiday?.name}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-left text-sm">
                                                            {isPast ? (
                                                                <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200/60">
                                                                    <span className="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                                                    ผ่านมาแล้ว
                                                                </span>
                                                            ) : (
                                                                <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                                                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                                    กำลังจะถึง
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            {isPast ? (
                                                                <div className="flex items-center justify-end gap-1 text-gray-400" title="วันหยุดที่ผ่านมาแล้วไม่สามารถลบได้">
                                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                                    </svg>
                                                                    <span className="text-xs">ล็อก</span>
                                                                </div>
                                                            ) : (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => {
                                                                        if (confirm(`คุณแน่ใจหรือไม่ที่จะลบวันหยุด "${holiday?.name}"?`)) {
                                                                            router.delete(route('holidays.destroy', holiday.id));
                                                                        }
                                                                    }}
                                                                    className="inline-flex items-center gap-1 text-red-600 hover:text-red-800 transition-colors px-2 py-1 rounded hover:bg-red-50"
                                                                >
                                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                    </svg>
                                                                    ลบ
                                                                </button>
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-gray-500 text-sm italic py-4">
                                    ไม่มีข้อมูลวันหยุดสำหรับปี {tableYear === 'all' ? 'ที่เลือก' : parseInt(tableYear) + 543} ({tableYear})
                                </p>
                            );
                        })()}
                    </div>

                </div>
            </div>
        </>
    );
}
