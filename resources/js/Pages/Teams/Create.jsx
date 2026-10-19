import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Create({ managers = [], technicians = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        code: '',
        description: '',
        manager_id: '',
        is_active: true,
        member_ids: [],
    });

    const handleMemberToggle = (id) => {
        const currentMembers = Array.isArray(data.member_ids) ? data.member_ids : [];
        const nextMembers = currentMembers.includes(id)
            ? currentMembers.filter((item) => item !== id)
            : [...currentMembers, id];
        setData('member_ids', nextMembers);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('teams.store'));
    };

    return (
        <>
            <Head title="สร้างทีมใหม่ (Create Team)" />

            <div className="py-6 sm:py-8 bg-gray-50 min-h-screen">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div>
                            <div className="flex items-center gap-2">
                                <Link
                                    href={route('teams.index')}
                                    className="p-1.5 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition"
                                >
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </Link>
                                <h1 className="text-2xl font-black text-gray-900 tracking-tight">
                                    สร้างทีมใหม่ (Create Team)
                                </h1>
                            </div>
                            <p className="text-sm text-gray-500 mt-1 ml-9">
                                กำหนดชื่อทีม รหัสย่อ และเลือกหัวหน้างานพร้อมสมาชิกในทีมผู้รับผิดชอบ
                            </p>
                        </div>
                    </div>

                    {/* Form Card */}
                    <div className="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-8">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Team Name & Code */}
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div className="sm:col-span-2">
                                    <InputLabel htmlFor="name" value="ชื่อทีม (Team Name) *" />
                                    <TextInput
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="เช่น Software, Hardware, Network, SAP ERP"
                                        className="mt-1 block w-full"
                                        required
                                        autoFocus
                                    />
                                    <InputError message={errors.name} className="mt-1" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="code" value="รหัสย่อ (Code) *" />
                                    <TextInput
                                        id="code"
                                        type="text"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                        placeholder="เช่น SW, HW, ERP"
                                        className="mt-1 block w-full font-mono uppercase"
                                        required
                                        maxLength="20"
                                    />
                                    <InputError message={errors.code} className="mt-1" />
                                </div>
                            </div>

                            {/* Description */}
                            <div>
                                <InputLabel htmlFor="description" value="คำอธิบายหน้าที่ความรับผิดชอบ" />
                                <textarea
                                    id="description"
                                    rows="3"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="อธิบายขอบเขตงาน เช่น ดูแลระบบคอมพิวเตอร์ ระบบเครือข่าย หรือแอปพลิเคชันภายในองค์กร..."
                                    className="mt-1 block w-full rounded-xl border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                                <InputError message={errors.description} className="mt-1" />
                            </div>

                            {/* Manager (Supervisor) */}
                            <div>
                                <InputLabel htmlFor="manager_id" value="หัวหน้าทีมผู้ดูแล (Team Manager / Supervisor)" />
                                <select
                                    id="manager_id"
                                    value={data.manager_id}
                                    onChange={(e) => setData('manager_id', e.target.value)}
                                    className="mt-1 block w-full rounded-xl border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">-- ยังไม่ระบุหัวหน้าทีม (กำหนดภายหลังได้) --</option>
                                    {managers.map((m) => (
                                        <option key={m.id} value={m.id}>
                                            {m.name} ({m.email}) [{m.role}]
                                        </option>
                                    ))}
                                </select>
                                <p className="text-xs text-gray-500 mt-1">
                                    💡 หัวหน้าทีมจะมีสิทธิ์เห็นรายงานภาระงาน ตรวจสอบใบงาน และอนุมัติปิดงานของลูกน้องในทีมนี้
                                </p>
                                <InputError message={errors.manager_id} className="mt-1" />
                            </div>

                            {/* Technicians / Members */}
                            <div>
                                <div className="flex items-center justify-between mb-2">
                                    <InputLabel value="สมาชิกในทีมผู้รับผิดชอบ (Team Members)" />
                                    <span className="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">
                                        เลือกแล้ว {(Array.isArray(data.member_ids) ? data.member_ids : []).length} คน
                                    </span>
                                </div>

                                {technicians.length === 0 ? (
                                    <div className="p-4 bg-gray-50 rounded-xl border border-gray-200 text-xs text-gray-500 text-center">
                                        ยังไม่มีรายชื่อสมาชิกในระบบ สามารถสร้างทีมไว้ก่อน แล้วเพิ่มผู้ใช้งานเข้ามาในภายหลังได้
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-y-auto p-2 bg-gray-50/60 rounded-xl border border-gray-200">
                                        {technicians.map((tech) => {
                                            const memberIds = Array.isArray(data.member_ids) ? data.member_ids : [];
                                            const isSelected = memberIds.includes(tech.id);
                                            return (
                                                <label
                                                    key={tech.id}
                                                    className={`flex items-center gap-2.5 p-2.5 rounded-lg border transition cursor-pointer ${
                                                        isSelected
                                                            ? 'bg-indigo-50/90 border-indigo-300 ring-1 ring-indigo-200'
                                                            : 'bg-white border-gray-200 hover:bg-gray-100/70'
                                                    }`}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={isSelected}
                                                        onChange={() => handleMemberToggle(tech.id)}
                                                        className="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                                                    />
                                                    <div className="min-w-0 flex-1">
                                                        <div className="text-xs font-bold text-gray-900 truncate">
                                                            {tech.name}
                                                        </div>
                                                        <div className="text-[11px] text-gray-500 truncate">
                                                            {tech.email}
                                                        </div>
                                                    </div>
                                                </label>
                                            );
                                        })}
                                    </div>
                                )}
                                <InputError message={errors.member_ids} className="mt-1" />
                            </div>

                            {/* Active Toggle */}
                            <div className="pt-2 border-t border-gray-100 flex items-center justify-between">
                                <div>
                                    <label htmlFor="is_active" className="text-sm font-bold text-gray-900 cursor-pointer">
                                        เปิดใช้งานทีมนี้ทันที (Active)
                                    </label>
                                    <p className="text-xs text-gray-500">
                                        ทีมที่เปิดใช้งานจะปรากฏให้เลือกในขั้นตอนการส่งต่องาน (Triage / Escalate)
                                    </p>
                                </div>
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="rounded text-indigo-600 focus:ring-indigo-500 h-5 w-5 cursor-pointer"
                                />
                            </div>

                            {/* Form Actions */}
                            <div className="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                                <Link
                                    href={route('teams.index')}
                                    className="px-4 py-2.5 rounded-xl border border-gray-300 text-xs font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                                >
                                    ยกเลิก
                                </Link>

                                <PrimaryButton disabled={processing} className="rounded-xl px-5 py-2.5 text-xs font-bold shadow-sm">
                                    {processing ? 'กำลังบันทึก...' : 'บันทึกสร้างทีม'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
