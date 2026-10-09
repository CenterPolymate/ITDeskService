import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout isRegister={false}>
            <Head title="Log in" />

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-6">
                {/* Login (Email or Username) */}
                <div>
                    <label htmlFor="login" className="block text-sm font-medium text-gray-700 font-semibold mb-1">อีเมล (Email)</label>
                    <div className="relative mt-1">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                        <input
                            id="login"
                            type="text"
                            name="login"
                            value={data.login}
                            required
                            autoFocus
                            autoComplete="username"
                            placeholder="อีเมล"
                            className="block w-full pl-10 pr-3 py-2 bg-white bg-opacity-70 border border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-lg shadow-sm transition duration-150 ease-in-out sm:text-sm"
                            onChange={(e) => setData('login', e.target.value)}
                        />
                    </div>
                    <InputError message={errors.login} className="mt-2 text-sm text-red-600" />
                </div>

                {/* Password */}
                <div>
                    <label htmlFor="password" className="block text-sm font-medium text-gray-700 font-semibold mb-1">รหัสผ่าน (Password)</label>
                    <div className="relative mt-1">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            required
                            autoComplete="current-password"
                            placeholder="••••••••"
                            className="block w-full pl-10 pr-3 py-2 bg-white bg-opacity-70 border border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-lg shadow-sm transition duration-150 ease-in-out sm:text-sm"
                            onChange={(e) => setData('password', e.target.value)}
                        />
                    </div>
                    <InputError message={errors.password} className="mt-2 text-sm text-red-600" />
                </div>

                {/* Remember Me & Forgot Password */}
                <div className="flex items-center justify-between">
                    <label htmlFor="remember_me" className="inline-flex items-center cursor-pointer">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        />
                        <span className="ms-2 text-sm text-gray-600 hover:text-gray-900 transition-colors">จดจำฉันในครั้งต่อไป</span>
                    </label>

                    <a
                        href={route('password.request')}
                        className="text-sm text-indigo-600 hover:text-indigo-900 hover:underline transition-colors focus:outline-none focus:underline"
                    >
                        ลืมรหัสผ่าน?
                    </a>
                </div>

                <div>
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-md text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transform transition hover:-translate-y-0.5 duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        เข้าสู่ระบบ (Log in)
                    </button>
                </div>

                <div className="mt-6 text-center">
                    <p className="text-sm text-gray-600">
                        ยังไม่มีบัญชีผู้ใช้งาน?{' '}
                        <Link href={route('register')} className="font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
                            สมัครสมาชิก (Register)
                        </Link>
                    </p>
                </div>
            </form>
        </GuestLayout>
    );
}
