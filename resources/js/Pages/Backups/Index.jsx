import React, { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';

export default function Index({ backups = [] }) {
    const { flash } = usePage().props;
    const [backupType, setBackupType] = useState('db_only');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleCreateBackup = (e) => {
        e.preventDefault();
        if (!confirm('การสำรองข้อมูลอาจใช้เวลาสักครู่ คุณต้องการดำเนินการต่อหรือไม่?')) {
            return;
        }

        setIsSubmitting(true);
        router.post(
            route('backups.store'),
            { type: backupType },
            {
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    const handleDeleteBackup = (fileName) => {
        if (!confirm(`คุณต้องการลบไฟล์สำรองข้อมูล "${fileName}" ใช่หรือไม่?`)) {
            return;
        }

        router.delete(route('backups.destroy'), {
            data: { file_name: fileName },
        });
    };

    return (
        <>
            <Head title="ระบบสำรองข้อมูล (Backup)" />

            <div className="py-6 sm:py-8 bg-gray-50 min-h-screen">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h1 className="text-2xl font-black text-gray-900 tracking-tight">
                                ระบบสำรองข้อมูล (Backup)
                            </h1>
                            <p className="text-sm text-gray-500 mt-1">
                                จัดการไฟล์สำรองข้อมูลฐานข้อมูลและไฟล์แนบของระบบ ITDeskService
                            </p>
                        </div>
                    </div>

                    {/* Flash messages */}
                    {flash?.success && (
                        <div className="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm flex items-start gap-3">
                            <svg className="w-5 h-5 text-emerald-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span className="text-sm font-medium leading-5">{flash.success}</span>
                        </div>
                    )}

                    {flash?.error && (
                        <div className="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-sm flex items-start gap-3">
                            <svg className="w-5 h-5 text-rose-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <strong className="font-semibold text-sm block">เกิดข้อผิดพลาด</strong>
                                <span className="text-xs text-rose-700 block mt-0.5 whitespace-pre-line">{flash.error}</span>
                            </div>
                        </div>
                    )}

                    {flash?.backup_output && (
                        <div className="bg-slate-900 border border-slate-800 text-slate-300 p-5 rounded-2xl shadow-lg">
                            <div className="font-mono text-xs font-bold text-slate-400 mb-2 flex items-center gap-2">
                                <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Terminal Output:
                            </div>
                            <pre className="text-xs font-mono overflow-x-auto whitespace-pre-wrap text-emerald-400 max-h-60 overflow-y-auto">
                                {flash.backup_output}
                            </pre>
                        </div>
                    )}

                    {/* Create Backup Card */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-6">
                        <h3 className="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                            <span className="w-2 h-2 rounded-full bg-indigo-500"></span>
                            สร้างไฟล์สำรองข้อมูล (Create Backup)
                        </h3>
                        <p className="text-sm text-gray-500 mb-4">
                            คุณสามารถสำรองข้อมูลระบบแบบเต็ม (Full Backup) ซึ่งจะรวมทั้งฐานข้อมูลและไฟล์แนบ หรือสำรองเฉพาะฐานข้อมูล (Database Only) เพื่อประหยัดพื้นที่
                        </p>

                        <form onSubmit={handleCreateBackup} className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <select
                                value={backupType}
                                onChange={(e) => setBackupType(e.target.value)}
                                disabled={isSubmitting}
                                className="w-full sm:w-auto border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm"
                            >
                                <option value="db_only">สำรองเฉพาะฐานข้อมูล (แนะนำ)</option>
                                <option value="full">สำรองทั้งหมด (ฐานข้อมูล + ไฟล์รูปภาพแนบ)</option>
                            </select>

                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-5 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-indigo-700 active:bg-indigo-900 shadow-sm transition disabled:opacity-50"
                            >
                                {isSubmitting ? (
                                    <>
                                        <svg className="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span>กำลังสำรองข้อมูล...</span>
                                    </>
                                ) : (
                                    <>
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        <span>เริ่มสำรองข้อมูล</span>
                                    </>
                                )}
                            </button>
                        </form>
                    </div>

                    {/* Backup History Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-6">
                        <div className="flex justify-between items-center mb-4">
                            <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                                ประวัติการสำรองข้อมูล (Backup History)
                            </h3>
                            <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                                ทั้งหมด {backups.length} ไฟล์
                            </span>
                        </div>

                        {backups.length > 0 ? (
                            <>
                                {/* Mobile Card View */}
                                <div className="md:hidden space-y-4">
                                    {backups.map((backup) => (
                                        <div key={backup.file_name} className="bg-white border border-gray-200 rounded-xl shadow-xs p-4 space-y-3">
                                            <div className="flex justify-between items-start gap-2">
                                                <h4 className="text-sm font-bold text-gray-900 font-mono break-all">
                                                    {backup.file_name}
                                                </h4>
                                                <span
                                                    className={`px-2 py-0.5 rounded text-[11px] font-bold shrink-0 ${
                                                        backup.type === 'เต็มระบบ'
                                                            ? 'bg-blue-100 text-blue-800'
                                                            : 'bg-emerald-100 text-emerald-800'
                                                    }`}
                                                >
                                                    {backup.type}
                                                </span>
                                            </div>

                                            <div className="text-xs text-gray-600 space-y-1.5 pt-2 border-t border-gray-100">
                                                <div className="flex justify-between">
                                                    <span className="text-gray-400">ขนาดไฟล์:</span>
                                                    <span className="font-semibold text-gray-700">{backup.file_size}</span>
                                                </div>
                                                <div className="flex justify-between">
                                                    <span className="text-gray-400">เวลาที่สำรอง:</span>
                                                    <span className="text-gray-700">{backup.last_modified}</span>
                                                </div>
                                            </div>

                                            <div className="flex gap-2 pt-2 border-t border-gray-100">
                                                <a
                                                    href={route('backups.download', { file_name: backup.file_name })}
                                                    className="flex-1 text-center font-bold text-xs text-indigo-700 bg-indigo-50 hover:bg-indigo-100 py-2 rounded-lg transition"
                                                >
                                                    ดาวน์โหลด
                                                </a>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDeleteBackup(backup.file_name)}
                                                    className="flex-1 text-center font-bold text-xs text-rose-700 bg-rose-50 hover:bg-rose-100 py-2 rounded-lg transition"
                                                >
                                                    ลบ
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Desktop Table View */}
                                <div className="hidden md:block overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 border border-gray-100 rounded-xl overflow-hidden">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ชื่อไฟล์</th>
                                                <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ประเภท</th>
                                                <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ขนาด</th>
                                                <th className="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">เวลาที่สำรอง</th>
                                                <th className="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {backups.map((backup) => (
                                                <tr key={backup.file_name} className="hover:bg-gray-50 transition-colors">
                                                    <td className="px-4 py-3 text-sm font-mono text-gray-900">{backup.file_name}</td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm">
                                                        {backup.type === 'เต็มระบบ' ? (
                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                                                เต็มระบบ (Full)
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                                ฐานข้อมูล (DB Only)
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600 font-mono">{backup.file_size}</td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{backup.last_modified}</td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-right text-sm">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <a
                                                                href={route('backups.download', { file_name: backup.file_name })}
                                                                className="inline-flex items-center gap-1 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition"
                                                            >
                                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                                </svg>
                                                                ดาวน์โหลด
                                                            </a>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDeleteBackup(backup.file_name)}
                                                                className="inline-flex items-center gap-1 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition"
                                                            >
                                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                                ลบ
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </>
                        ) : (
                            <div className="text-center py-10 text-gray-500">
                                <svg className="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                                ยังไม่มีประวัติการสำรองข้อมูล
                            </div>
                        )}
                    </div>

                    {/* Automated Backup Info Card */}
                    <div className="bg-blue-50 border border-blue-200 rounded-2xl p-5">
                        <div className="flex items-start gap-3">
                            <div className="shrink-0 p-2 bg-blue-100 text-blue-600 rounded-xl">
                                <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="flex-1">
                                <h3 className="text-sm font-bold text-blue-900">คำแนะนำการตั้งค่าสำรองข้อมูลอัตโนมัติ (Automated Backup)</h3>
                                <div className="mt-2 text-xs text-blue-700 space-y-2">
                                    <p>กรุณาเพิ่มคำสั่ง <strong>Cron Job</strong> ในระบบจัดการเซิร์ฟเวอร์ (เช่น CloudPanel) โดยกำหนดระยะเวลาการทำงานเป็นทุก 1 นาที (<code>* * * * *</code>) ด้วยคำสั่งต่อไปนี้:</p>
                                    <div className="overflow-x-auto bg-blue-100 p-2.5 rounded-lg border border-blue-200 font-mono text-xs text-blue-900">
                                        <code>* * * * * cd /home/htdocs/itdeskservice && php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
                                    </div>
                                    <p className="text-[11px] text-blue-600">
                                        <i>หมายเหตุ: คำสั่งนี้จะทำการสำรองข้อมูลทั้งหมด (Full Backup) อัตโนมัติเวลา 00:00 น. และลบไฟล์เก่าที่หมดอายุเวลา 00:30 น. ของทุกวัน</i>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* How to Restore Guide Card */}
                    <div className="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                        <div className="flex items-start gap-3">
                            <div className="shrink-0 p-2 bg-amber-100 text-amber-600 rounded-xl">
                                <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="flex-1">
                                <h3 className="text-sm font-bold text-amber-900">คู่มือการกู้คืนข้อมูล (How to Restore)</h3>
                                <div className="mt-2 text-xs text-amber-800 space-y-2">
                                    <p>เพื่อป้องกันปัญหาฐานข้อมูลเสียหายจาก Time-out ระหว่างการทำงานของเว็บไซต์ <strong>ระบบนี้จึงไม่มีปุ่ม Restore ผ่านหน้าเว็บโดยตรง</strong> หากเกิดเหตุฉุกเฉินและต้องการกู้คืนข้อมูล ให้ดำเนินการผ่านเซิร์ฟเวอร์ดังนี้:</p>
                                    <ol className="list-decimal list-inside space-y-1 pl-1">
                                        <li>กด <strong>"ดาวน์โหลด"</strong> ไฟล์ <code>.zip</code> จากตารางด้านบนไปไว้ที่เครื่องคอมพิวเตอร์ของคุณ</li>
                                        <li>แตกไฟล์ <code>.zip</code> จะพบไฟล์ <code>.sql</code> ในโฟลเดอร์ <code>db-dumps</code> และไฟล์รูปภาพ</li>
                                        <li>นำไฟล์ <code>.sql</code> ไป Import เข้าฐานข้อมูลเดิมผ่าน <strong>phpMyAdmin</strong> หรือเมนู Databases ใน CloudPanel</li>
                                        <li>(หากเป็น Full Backup) ให้นำไฟล์รูปภาพทั้งหมด อัปโหลดไปวางทับในโฟลเดอร์ <code>/storage/app/public</code> ของเซิร์ฟเวอร์</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
