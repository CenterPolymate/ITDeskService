import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function Register({ companies }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        company: '',
        department: '',
        phone: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const [departments, setDepartments] = useState([]);
    const [selectedDepartment, setSelectedDepartment] = useState('');
    const [customDepartment, setCustomDepartment] = useState('');

    useEffect(() => {
        if (!data.company) {
            setDepartments([]);
            return;
        }
        const company = companies.find(c => c.name === data.company);
        setDepartments(company && company.departments ? company.departments : []);
        setSelectedDepartment('');
        setCustomDepartment('');
        setData('department', '');
    }, [data.company]);

    useEffect(() => {
        if (selectedDepartment === 'other' || departments.length === 0) {
            setData('department', customDepartment);
        } else {
            setData('department', selectedDepartment);
        }
    }, [selectedDepartment, customDepartment, departments]);


    const submit = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout isRegister={true}>
            <Head title="Register" />

            <form onSubmit={submit} className="space-y-5">
                {/* Name */}
                <div>
                    <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล <span className="text-red-500">*</span></label>
                    <div className="relative">
                        <input 
                            id="name" 
                            type="text" 
                            name="name" 
                            value={data.name} 
                            required 
                            autoFocus 
                            autoComplete="name" 
                            placeholder="ระบุชื่อ-นามสกุลของคุณ" 
                            className="block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200"
                            onChange={(e) => setData('name', e.target.value)}
                        />
                    </div>
                    <InputError message={errors.name} className="mt-2 text-sm text-red-600" />
                </div>

                {/* Company */}
                <div>
                    <label htmlFor="company" className="block text-sm font-medium text-gray-700 mb-1">บริษัท (Company) <span className="text-red-500">*</span></label>
                    <div className="relative">
                        <select 
                            id="company" 
                            name="company" 
                            required 
                            value={data.company}
                            onChange={(e) => setData('company', e.target.value)}
                            className="block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200"
                        >
                            <option value="" disabled>เลือกบริษัทของคุณ</option>
                            {companies.map(comp => (
                                <option key={comp.id} value={comp.name}>{comp.name}</option>
                            ))}
                        </select>
                    </div>
                    <InputError message={errors.company} className="mt-2 text-sm text-red-600" />
                </div>

                {/* Department */}
                <div>
                    <label htmlFor="department" className="block text-sm font-medium text-gray-700 mb-1">แผนก (Department) <span className="text-red-500">*</span></label>
                    
                    {departments.length > 0 && selectedDepartment !== 'other' && (
                        <div className="relative">
                            <select 
                                id="department_select" 
                                value={selectedDepartment}
                                onChange={(e) => setSelectedDepartment(e.target.value)}
                                className="block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200"
                                required={true}
                                disabled={!data.company}
                            >
                                <option value="">กรุณาเลือกแผนกที่ท่านสังกัด</option>
                                {departments.map(dept => (
                                    <option key={dept.id} value={dept.name}>{dept.name}</option>
                                ))}
                                <option value="other">อื่นๆ (พิมพ์ระบุเอง)</option>
                            </select>
                        </div>
                    )}

                    {(departments.length === 0 || selectedDepartment === 'other') && (
                        <div className={`relative ${departments.length > 0 && selectedDepartment === 'other' ? 'mt-2' : ''}`}>
                            <input 
                                id="department" 
                                type="text" 
                                value={customDepartment}
                                onChange={(e) => setCustomDepartment(e.target.value)}
                                placeholder="กรุณาระบุแผนกที่ท่านสังกัด" 
                                className="block w-full pl-3 pr-24 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200"
                                required={true}
                                disabled={!data.company}
                            />
                            {departments.length > 0 && selectedDepartment === 'other' && (
                                <button type="button" onClick={() => setSelectedDepartment('')} className="absolute inset-y-0 right-0 pr-3 flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">กลับไปเลือก</button>
                            )}
                        </div>
                    )}

                    {data.company && departments.length === 0 && (
                        <p className="mt-1 text-xs text-gray-500">บริษัทนี้ยังไม่มีการตั้งค่าแผนก กรุณาพิมพ์ระบุเอง</p>
                    )}
                    {!data.company && (
                        <p className="mt-1 text-xs text-orange-500">กรุณาเลือกบริษัทก่อน</p>
                    )}
                    
                    <InputError message={errors.department} className="mt-2 text-sm text-red-600" />
                </div>

                {/* Phone */}
                <div>
                    <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์ (Phone)</label>
                    <div className="relative">
                        <input 
                            id="phone" 
                            type="text" 
                            name="phone" 
                            value={data.phone} 
                            placeholder="ระบุเบอร์โทรศัพท์ของคุณ (ไม่บังคับ)" 
                            className="block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200"
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                    </div>
                    <InputError message={errors.phone} className="mt-2 text-sm text-red-600" />
                </div>

                {/* Email */}
                <div>
                    <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">อีเมลบริษัท (Company Email) <span className="text-red-500">*</span></label>
                    <div className="relative">
                        <input 
                            id="email" 
                            type="email" 
                            name="email" 
                            value={data.email} 
                            required 
                            autoComplete="username" 
                            placeholder="name@company.com" 
                            className="block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200"
                            onChange={(e) => setData('email', e.target.value)}
                        />
                    </div>
                    <InputError message={errors.email} className="mt-2 text-sm text-red-600" />
                </div>

                {/* Password */}
                <div>
                    <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน (Password) <span className="text-red-500">*</span></label>
                    <div className="relative">
                        <input 
                            id="password" 
                            type="password" 
                            name="password" 
                            value={data.password}
                            required 
                            autoComplete="new-password" 
                            placeholder="••••••••" 
                            className="block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200"
                            onChange={(e) => setData('password', e.target.value)}
                        />
                    </div>
                    <InputError message={errors.password} className="mt-2 text-sm text-red-600" />
                </div>

                {/* Confirm Password */}
                <div>
                    <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700 mb-1">ยืนยันรหัสผ่าน (Confirm Password) <span className="text-red-500">*</span></label>
                    <div className="relative">
                        <input 
                            id="password_confirmation" 
                            type="password" 
                            name="password_confirmation" 
                            value={data.password_confirmation}
                            required 
                            autoComplete="new-password" 
                            placeholder="••••••••" 
                            className="block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200"
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                    </div>
                    <InputError message={errors.password_confirmation} className="mt-2 text-sm text-red-600" />
                </div>

                <div className="flex items-center justify-between mt-6">
                    <Link href={route('login')} className="text-sm text-indigo-600 hover:text-indigo-500 font-medium transition duration-150 ease-in-out">
                        มีบัญชีอยู่แล้ว? เข้าสู่ระบบ
                    </Link>

                    <button 
                        type="submit" 
                        disabled={processing}
                        className="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-md text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transform transition hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        สมัครสมาชิก
                    </button>
                </div>
            </form>
        </GuestLayout>
    );
}
