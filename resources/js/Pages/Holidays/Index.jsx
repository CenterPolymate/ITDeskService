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

    const [selectedYear, setSelectedYear] = useState(fetchYear || new Date().getFullYear());
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
                                    className="mt-1 block w-full"
                                    value={manualForm.data.date}
                                    onChange={(e) => manualForm.setData('date', e.target.value)}
                                    required
                                />
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
                        <h3 className="text-lg font-bold text-gray-900 mb-4">รายการวันหยุดในระบบทั้งหมด</h3>
                        {holidays && holidays.length > 0 ? (
                            <div className="overflow-x-auto rounded-lg border border-gray-200">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อวันหยุด</th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {holidays?.map(holiday => (
                                            <tr key={holiday.id} className="hover:bg-gray-50 transition-colors">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {holiday?.date ? new Date(holiday.date).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : '-'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {holiday?.name}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button
                                                        onClick={() => {
                                                            if (confirm('คุณแน่ใจหรือไม่ที่จะลบวันหยุดนี้?')) {
                                                                router.delete(route('holidays.destroy', holiday.id));
                                                            }
                                                        }}
                                                        className="text-red-600 hover:text-red-900 transition-colors"
                                                    >
                                                        ลบ
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-gray-500 text-sm italic">ยังไม่มีข้อมูลวันหยุดในระบบ</p>
                        )}
                    </div>

                </div>
            </div>
        </>
    );
}
