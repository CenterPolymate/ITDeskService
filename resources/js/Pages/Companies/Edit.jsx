import React, { useState } from 'react';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';

export default function Edit({ company, unmappedDepartments = [] }) {
    const { flash } = usePage().props;

    // Company Edit Form
    const companyForm = useForm({
        name: company.name || '',
        short_name: company.short_name || '',
        email_domains: company.email_domains || '',
        sla_type: company.sla_type || '8x5x4',
        is_active: Boolean(company.is_active),
    });

    const handleCompanySubmit = (e) => {
        e.preventDefault();
        companyForm.put(route('companies.update', company.id));
    };

    // Add Department Form
    const [deptName, setDeptName] = useState('');
    const [addingDept, setAddingDept] = useState(false);

    const handleAddDepartment = (e) => {
        e.preventDefault();
        if (!deptName.trim()) return;
        setAddingDept(true);
        router.post(
            route('companies.departments.store', company.id),
            { name: deptName.trim() },
            {
                onSuccess: () => setDeptName(''),
                onFinish: () => setAddingDept(false),
            }
        );
    };

    // Inline Department Edit
    const [editingDeptId, setEditingDeptId] = useState(null);
    const [editingDeptName, setEditingDeptName] = useState('');

    const handleStartEditDept = (dept) => {
        setEditingDeptId(dept.id);
        setEditingDeptName(dept.name);
    };

    const handleSaveEditDept = (deptId) => {
        if (!editingDeptName.trim()) return;
        router.put(
            route('companies.departments.update', deptId),
            { name: editingDeptName.trim() },
            {
                onSuccess: () => setEditingDeptId(null),
            }
        );
    };

    const handleDeleteDept = (dept) => {
        if (confirm(`คุณแน่ใจหรือไม่ที่จะลบหน่วยงาน "${dept.name}"?`)) {
            router.delete(route('companies.departments.destroy', dept.id));
        }
    };

    // Map Department Modal State
    const [showMapModal, setShowMapModal] = useState(false);
    const [mapOriginalName, setMapOriginalName] = useState('');
    const [mapAction, setMapAction] = useState('create');
    const [mapNewName, setMapNewName] = useState('');
    const [mapTargetId, setMapTargetId] = useState('');
    const [mapping, setMapping] = useState(false);

    const handleOpenMapModal = (unmappedName) => {
        setMapOriginalName(unmappedName);
        setMapNewName(unmappedName);
        setMapAction('create');
        setMapTargetId('');
        setShowMapModal(true);
    };

    const handleMapSubmit = (e) => {
        e.preventDefault();
        setMapping(true);
        router.post(
            route('companies.departments.map', company.id),
            {
                original_name: mapOriginalName,
                action: mapAction,
                new_name: mapAction === 'create' ? mapNewName : null,
                target_department_id: mapAction === 'merge' ? mapTargetId : null,
            },
            {
                onSuccess: () => setShowMapModal(false),
                onFinish: () => setMapping(false),
            }
        );
    };

    return (
        <>
            <Head title={`แก้ไขบริษัท - ${company.name}`} />

            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        แก้ไขบริษัท: {company.name}
                    </h2>
                </div>
            </header>

            <div className="py-4 sm:py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {flash?.success && (
                        <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative flex items-center justify-between">
                            <span>{flash.success}</span>
                        </div>
                    )}
                    {flash?.error && (
                        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative flex items-center justify-between">
                            <span>{flash.error}</span>
                        </div>
                    )}

                    {/* Section 1: Company Info */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900 border-b border-gray-200">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">ข้อมูลบริษัท</h3>
                            <form onSubmit={handleCompanySubmit}>
                                <div className="mb-4">
                                    <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                        ชื่อบริษัท <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="name"
                                        required
                                        value={companyForm.data.name}
                                        onChange={(e) => companyForm.setData('name', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {companyForm.errors.name && (
                                        <p className="mt-1 text-sm text-red-600">{companyForm.errors.name}</p>
                                    )}
                                </div>

                                <div className="mb-4">
                                    <label htmlFor="short_name" className="block text-sm font-medium text-gray-700">
                                        ชื่อย่อ (ถ้ามี)
                                    </label>
                                    <input
                                        type="text"
                                        id="short_name"
                                        value={companyForm.data.short_name}
                                        onChange={(e) => companyForm.setData('short_name', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {companyForm.errors.short_name && (
                                        <p className="mt-1 text-sm text-red-600">{companyForm.errors.short_name}</p>
                                    )}
                                </div>

                                <div className="mb-4">
                                    <label htmlFor="email_domains" className="block text-sm font-medium text-gray-700">
                                        โดเมนอีเมลที่อนุญาต (คั่นด้วยลูกน้ำ) <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="email_domains"
                                        required
                                        placeholder="เช่น @polymate.co.th, @gmail.com"
                                        value={companyForm.data.email_domains}
                                        onChange={(e) => companyForm.setData('email_domains', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {companyForm.errors.email_domains && (
                                        <p className="mt-1 text-sm text-red-600">{companyForm.errors.email_domains}</p>
                                    )}
                                </div>

                                <div className="mb-4">
                                    <label htmlFor="sla_type" className="block text-sm font-medium text-gray-700">
                                        ประเภท SLA (SLA Type) <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="sla_type"
                                        required
                                        value={companyForm.data.sla_type}
                                        onChange={(e) => companyForm.setData('sla_type', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="8x5x4">8x5x4 (จันทร์-ศุกร์ 08:00-17:00 / ภายใน 4 ชม.)</option>
                                        <option value="24x7x4">24x7x4 (ตลอด 24 ชั่วโมง / ภายใน 4 ชม.)</option>
                                    </select>
                                    {companyForm.errors.sla_type && (
                                        <p className="mt-1 text-sm text-red-600">{companyForm.errors.sla_type}</p>
                                    )}
                                </div>

                                <div className="mb-6">
                                    <label className="inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={companyForm.data.is_active}
                                            onChange={(e) => companyForm.setData('is_active', e.target.checked)}
                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        />
                                        <span className="ml-2 text-sm text-gray-600">เปิดใช้งาน</span>
                                    </label>
                                </div>

                                <div className="flex items-center justify-end mt-4 gap-3">
                                    <Link
                                        href={route('companies.index')}
                                        className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                                    >
                                        ยกเลิก
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={companyForm.processing}
                                        className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition shadow-sm disabled:opacity-50"
                                    >
                                        บันทึกการแก้ไข
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* Section 2: Manage Departments */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">
                                จัดการหน่วยงาน/แผนก (Departments)
                            </h3>

                            {/* Add Department Form */}
                            <form onSubmit={handleAddDepartment} className="mb-6 flex gap-3 items-end">
                                <div className="flex-1">
                                    <label htmlFor="dept_name" className="block text-sm font-medium text-gray-700 mb-1">
                                        เพิ่มหน่วยงานใหม่
                                    </label>
                                    <input
                                        type="text"
                                        id="dept_name"
                                        required
                                        placeholder="เช่น IT, HR, จัดซื้อ"
                                        value={deptName}
                                        onChange={(e) => setDeptName(e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    disabled={addingDept}
                                    className="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition shadow-sm h-[38px] disabled:opacity-50"
                                >
                                    เพิ่ม
                                </button>
                            </form>

                            {/* Unmapped Departments Warning Box */}
                            {unmappedDepartments && unmappedDepartments.length > 0 && (
                                <div className="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                                    <h4 className="text-sm font-medium text-amber-800 mb-2 flex items-center gap-1.5">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        แผนกที่มีผู้ใช้งานพิมพ์ระบุเข้ามาเอง (ยังไม่ได้เพิ่มในระบบ)
                                    </h4>
                                    <p className="text-xs text-amber-700 mb-3">
                                        คลิกที่ปุ่มเพื่อเพิ่มแผนกเหล่านี้เข้าสู่ระบบการเลือก (Dropdown)
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {unmappedDepartments.map((unmapped, idx) => (
                                            <button
                                                key={idx}
                                                type="button"
                                                onClick={() => handleOpenMapModal(unmapped)}
                                                className="inline-flex items-center px-3 py-1.5 bg-white hover:bg-amber-100 border border-amber-300 rounded-md text-xs font-medium text-amber-900 transition-colors shadow-sm"
                                                title="คลิกเพื่อจัดการแผนก"
                                            >
                                                {unmapped}
                                                <svg className="w-3.5 h-3.5 ml-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* List of Departments */}
                            {company.departments && company.departments.length > 0 ? (
                                <div className="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                                    <ul className="divide-y divide-gray-200">
                                        {company.departments.map((dept) => (
                                            <li key={dept.id} className="p-4 flex items-center justify-between hover:bg-gray-100 transition-colors">
                                                {editingDeptId === dept.id ? (
                                                    <div className="w-full flex gap-2">
                                                        <input
                                                            type="text"
                                                            value={editingDeptName}
                                                            onChange={(e) => setEditingDeptName(e.target.value)}
                                                            required
                                                            className="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                        />
                                                        <button
                                                            type="button"
                                                            onClick={() => handleSaveEditDept(dept.id)}
                                                            className="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                                                        >
                                                            บันทึก
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => setEditingDeptId(null)}
                                                            className="inline-flex items-center px-3 py-1 bg-gray-200 border border-transparent rounded-md text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition"
                                                        >
                                                            ยกเลิก
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <div className="flex-1 flex items-center justify-between">
                                                        <span className="text-sm font-medium text-gray-800">{dept.name}</span>
                                                        <div className="flex items-center gap-4">
                                                            <button
                                                                type="button"
                                                                onClick={() => handleStartEditDept(dept)}
                                                                className="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition-colors"
                                                            >
                                                                แก้ไข
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDeleteDept(dept)}
                                                                className="text-red-500 hover:text-red-700 text-sm font-medium transition-colors"
                                                            >
                                                                ลบ
                                                            </button>
                                                        </div>
                                                    </div>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500 italic">ยังไม่มีหน่วยงานสำหรับบริษัทนี้</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Map Department Modal */}
            {showMapModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div className="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div
                            className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                            aria-hidden="true"
                            onClick={() => setShowMapModal(false)}
                        ></div>
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div className="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                            <form onSubmit={handleMapSubmit}>
                                <div>
                                    <div className="flex items-center justify-center w-12 h-12 mx-auto bg-amber-100 rounded-full">
                                        <svg className="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                    </div>
                                    <div className="mt-3 text-center sm:mt-5">
                                        <h3 className="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                            จัดการแผนก: <span className="text-indigo-600">{mapOriginalName}</span>
                                        </h3>
                                        <div className="mt-2">
                                            <p className="text-sm text-gray-500">เลือกวิธีจัดการกับแผนกที่ผู้ใช้งานระบุเข้ามาเอง</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-5 space-y-4 text-left">
                                    {/* Option 1: Create New */}
                                    <label
                                        className={`flex p-3 border rounded-lg cursor-pointer hover:bg-gray-50 ${
                                            mapAction === 'create' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="mapAction"
                                            value="create"
                                            checked={mapAction === 'create'}
                                            onChange={() => setMapAction('create')}
                                            className="w-4 h-4 mt-0.5 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                                        />
                                        <div className="ml-3 w-full">
                                            <span className="block text-sm font-medium text-gray-900">
                                                เพิ่มเป็นแผนกใหม่ (Add & Correct)
                                            </span>
                                            <span className="block text-sm text-gray-500">
                                                สร้างแผนกใหม่ และสามารถแก้ไขชื่อให้ถูกต้องได้
                                            </span>

                                            {mapAction === 'create' && (
                                                <div className="mt-3">
                                                    <label className="block text-xs text-gray-700 mb-1">ชื่อแผนกใหม่:</label>
                                                    <input
                                                        type="text"
                                                        value={mapNewName}
                                                        onChange={(e) => setMapNewName(e.target.value)}
                                                        required
                                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    </label>

                                    {/* Option 2: Merge Existing */}
                                    <label
                                        className={`flex p-3 border rounded-lg cursor-pointer hover:bg-gray-50 ${
                                            mapAction === 'merge' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="mapAction"
                                            value="merge"
                                            checked={mapAction === 'merge'}
                                            onChange={() => setMapAction('merge')}
                                            className="w-4 h-4 mt-0.5 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                                        />
                                        <div className="ml-3 w-full">
                                            <span className="block text-sm font-medium text-gray-900">
                                                จับคู่กับแผนกเดิม (Merge)
                                            </span>
                                            <span className="block text-sm text-gray-500">
                                                ระบบจะทำการย้ายผู้ใช้ทั้งหมดในแผนกนี้ ไปยังแผนกที่เลือกไว้
                                            </span>

                                            {mapAction === 'merge' && (
                                                <div className="mt-3">
                                                    <label className="block text-xs text-gray-700 mb-1">เลือกแผนกเป้าหมาย:</label>
                                                    <select
                                                        value={mapTargetId}
                                                        onChange={(e) => setMapTargetId(e.target.value)}
                                                        required
                                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                    >
                                                        <option value="">-- เลือกแผนก --</option>
                                                        {company.departments &&
                                                            company.departments.map((dept) => (
                                                                <option key={dept.id} value={dept.id}>
                                                                    {dept.name}
                                                                </option>
                                                            ))}
                                                    </select>
                                                </div>
                                            )}
                                        </div>
                                    </label>
                                </div>

                                <div className="mt-6 sm:mt-6 sm:flex sm:flex-row-reverse gap-2">
                                    <button
                                        type="submit"
                                        disabled={mapping}
                                        className="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm disabled:opacity-50"
                                    >
                                        {mapping ? 'กำลังบันทึก...' : 'บันทึก'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowMapModal(false)}
                                        className="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                                    >
                                        ยกเลิก
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
