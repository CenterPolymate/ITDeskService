import React from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Edit({ team, managers = [], technicians = [], currentMemberIds = [] }) {
    const initialHasMembers = Array.isArray(currentMemberIds) && currentMemberIds.length > 0;
    const { data, setData, put, processing, errors } = useForm({
        name: team.name || '',
        code: team.code || '',
        description: team.description || '',
        manager_id: team.manager_id || '',
        is_active: initialHasMembers ? Boolean(team.is_active) : false,
        member_ids: currentMemberIds || [],
    });

    const [memberSearch, setMemberSearch] = React.useState('');

    const filteredTechnicians = React.useMemo(() => {
        if (!memberSearch.trim()) {
            return technicians;
        }
        const q = memberSearch.toLowerCase();
        return technicians.filter(
            (t) => t.name?.toLowerCase().includes(q) || t.email?.toLowerCase().includes(q)
        );
    }, [technicians, memberSearch]);

    const handleMemberToggle = (id) => {
        const currentMembers = Array.isArray(data.member_ids) ? data.member_ids : [];
        const nextMembers = currentMembers.includes(id)
            ? currentMembers.filter((item) => item !== id)
            : [...currentMembers, id];

        setData((prev) => ({
            ...prev,
            member_ids: nextMembers,
            is_active: nextMembers.length === 0 ? false : (prev.is_active || prev.member_ids.length === 0),
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('teams.update', team.id));
    };

    const handleDelete = () => {
        if (!confirm(`ยืนยันการลบทีม "${team.name}" (${team.code})? การลบนี้จะยกเลิกการสังกัดทีมของสมาชิกทุกคนในทีมนี้`)) {
            return;
        }
        router.delete(route('teams.destroy', team.id));
    };

    return (
        <>
            <Head title={`แก้ไขทีม ${team.name} (Edit Team)`} />

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
                                    แก้ไขทีม: {team.name}
                                </h1>
                            </div>
                            <p className="text-sm text-gray-500 mt-1 ml-9">
                                ปรับปรุงข้อมูลทีม เปลี่ยนหัวหน้างาน หรือจัดสรรสมาชิกผู้รับผิดชอบใหม่
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={handleDelete}
                            className="inline-flex items-center gap-1.5 px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl transition border border-rose-200 cursor-pointer"
                        >
                            <svg className="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>ลบทีมนี้</span>
                        </button>
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
                                        placeholder="เช่น Software, Hardware, Network"
                                        className="mt-1 block w-full"
                                        required
                                    />
                                    <InputError message={errors.name} className="mt-1" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="code" value="รหัสย่อ (Code) *" />
                                    <TextInput
                                        id="code"
                                        type="text"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, ''))}
                                        placeholder="เช่น SW, HW, ERP"
                                        className="mt-1 block w-full font-mono uppercase"
                                        required
                                        minLength="2"
                                        maxLength="5"
                                    />
                                    <p className="text-[11px] text-gray-500 mt-1">
                                        ภาษาอังกฤษหรือตัวเลข 2-5 ตัวอักษร (เช่น SW, HW, ERP)
                                    </p>
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
                                    placeholder="อธิบายขอบเขตงาน..."
                                    className="mt-1 block w-full rounded-xl border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                                <InputError message={errors.description} className="mt-1" />
                            </div>

                            {/* Manager (Supervisor) */}
                            <div>
                                <InputLabel htmlFor="manager_id">
                                    หัวหน้าทีมผู้ดูแล (Team Manager / Supervisor) <span className="text-rose-500">*</span>
                                </InputLabel>
                                <select
                                    id="manager_id"
                                    required
                                    value={data.manager_id}
                                    onChange={(e) => setData('manager_id', e.target.value)}
                                    className="mt-1 block w-full rounded-xl border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="" disabled>-- กรุณาเลือกหัวหน้าทีม --</option>
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
                                <div className="flex items-center justify-between mb-1">
                                    <InputLabel value="สมาชิกในทีมผู้รับผิดชอบ (Team Members)" />
                                    <span className="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">
                                        เลือกแล้ว {(Array.isArray(data.member_ids) ? data.member_ids : []).length} คน
                                    </span>
                                </div>
                                <p className="text-xs text-gray-500 mb-2.5">
                                    💡 ตามมาตรฐานสากล (ITSM) สมาชิก 1 คนสามารถสังกัดได้หลายทีมพร้อมกันตามทักษะความเชี่ยวชาญ
                                </p>

                                {technicians.length > 5 && (
                                    <div className="mb-2">
                                        <input
                                            type="text"
                                            value={memberSearch}
                                            onChange={(e) => setMemberSearch(e.target.value)}
                                            placeholder="🔍 ค้นหาชื่อ หรืออีเมลสมาชิก..."
                                            className="w-full text-xs rounded-xl border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3"
                                        />
                                    </div>
                                )}

                                {technicians.length === 0 ? (
                                    <div className="p-4 bg-gray-50 rounded-xl border border-gray-200 text-xs text-gray-500 text-center">
                                        ยังไม่มีรายชื่อสมาชิกในระบบ
                                    </div>
                                ) : filteredTechnicians.length === 0 ? (
                                    <div className="p-4 bg-gray-50 rounded-xl border border-gray-200 text-xs text-gray-500 text-center">
                                        ไม่พบสมาชิกที่ตรงกับคำค้นหา "{memberSearch}"
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[460px] overflow-y-auto p-2 bg-gray-50/60 rounded-xl border border-gray-200">
                                        {filteredTechnicians.map((tech) => {
                                            const memberIds = Array.isArray(data.member_ids) ? data.member_ids : [];
                                            const isSelected = memberIds.includes(tech.id);
                                            return (
                                                <label
                                                    key={tech.id}
                                                    className={`flex items-start gap-2.5 p-2.5 rounded-lg border transition cursor-pointer ${
                                                        isSelected
                                                            ? 'bg-indigo-50/90 border-indigo-300 ring-1 ring-indigo-200'
                                                            : 'bg-white border-gray-200 hover:bg-gray-100/70'
                                                    }`}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={isSelected}
                                                        onChange={() => handleMemberToggle(tech.id)}
                                                        className="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                                                    />
                                                    <div className="min-w-0 flex-1">
                                                        <div className="text-xs font-bold text-gray-900 truncate">
                                                            {tech.name}
                                                        </div>
                                                        <div className="text-[11px] text-gray-500 truncate">
                                                            {tech.email}
                                                        </div>
                                                        {tech.teams && tech.teams.length > 0 ? (
                                                            <div className="flex items-start gap-1.5 mt-1">
                                                                <span className="text-[10px] text-gray-400 shrink-0 pt-0.5">สังกัดอยู่:</span>
                                                                <div className="flex flex-wrap gap-1 flex-1">
                                                                    {tech.teams.map((t) => {
                                                                        const isThisTeam = t.id === team.id;
                                                                        return (
                                                                            <span
                                                                                key={t.id}
                                                                                className={`inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded border ${
                                                                                    isThisTeam
                                                                                        ? 'bg-indigo-50 text-indigo-700 border-indigo-200 font-bold'
                                                                                        : 'bg-slate-100 text-slate-700 border-slate-200'
                                                                                }`}
                                                                            >
                                                                                {t.code} - {t.name}
                                                                                {isThisTeam && ' (ทีมนี้)'}
                                                                            </span>
                                                                        );
                                                                    })}
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <div className="flex items-center gap-1.5 mt-1">
                                                                <span className="text-[10px] text-gray-400 shrink-0">สังกัดอยู่:</span>
                                                                <span className="inline-flex items-center text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                                                    ยังไม่สังกัดทีมใด
                                                                </span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </label>
                                            );
                                        })}
                                    </div>
                                )}
                                <InputError message={errors.member_ids} className="mt-1" />
                            </div>

                            {/* Active Toggle */}
                            <div className="pt-2 border-t border-gray-100 flex items-start justify-between gap-4">
                                <div>
                                    <label
                                        htmlFor="is_active"
                                        className={`text-sm font-bold ${!(Array.isArray(data.member_ids) && data.member_ids.length > 0) ? 'text-gray-400 cursor-not-allowed' : 'text-gray-900 cursor-pointer'}`}
                                    >
                                        เปิดใช้งานทีมนี้ (Active)
                                    </label>
                                    <p className="text-xs text-gray-500">
                                        ทีมที่เปิดใช้งานจะปรากฏให้เลือกในขั้นตอนการส่งต่องาน (Triage / Escalate)
                                    </p>
                                    {!(Array.isArray(data.member_ids) && data.member_ids.length > 0) && (
                                        <p className="text-xs text-amber-600 font-medium mt-1 flex items-center gap-1">
                                            <span>⚠️</span> ต้องเลือกสมาชิกในทีมอย่างน้อย 1 คน จึงจะสามารถเปิดใช้งานทีมนี้เพื่อรับงานได้
                                        </p>
                                    )}
                                </div>
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    disabled={!(Array.isArray(data.member_ids) && data.member_ids.length > 0)}
                                    checked={(Array.isArray(data.member_ids) && data.member_ids.length > 0) && Boolean(data.is_active)}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className={`rounded text-indigo-600 focus:ring-indigo-500 h-5 w-5 mt-0.5 ${
                                        !(Array.isArray(data.member_ids) && data.member_ids.length > 0)
                                            ? 'opacity-40 cursor-not-allowed bg-gray-100'
                                            : 'cursor-pointer'
                                    }`}
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
                                    {processing ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
