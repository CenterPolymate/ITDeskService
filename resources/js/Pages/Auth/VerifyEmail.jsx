import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="ยืนยันอีเมล (Email Verification)" />

            <div className="mb-4 text-sm text-gray-600 leading-relaxed">
                ขอบคุณที่สมัครสมาชิกเข้าใช้งานระบบ ITDeskService! ก่อนเริ่มต้นใช้งาน กรุณายืนยันที่อยู่อีเมลของคุณ โดยคลิกที่ลิงก์ที่เราได้ส่งไปยังอีเมลของคุณ หากคุณไม่ได้รับอีเมล สามารถกดปุ่มส่งใหม่อีกครั้งได้
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm font-medium text-emerald-700 flex items-center gap-2">
                    <svg className="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>ระบบได้ส่งลิงก์ยืนยันตัวตนใหม่ไปยังที่อยู่อีเมลของคุณเรียบร้อยแล้ว</span>
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-6 flex items-center justify-between">
                    <PrimaryButton disabled={processing} className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-md">
                        {processing ? 'กำลังส่งข้อมูล...' : 'ส่งอีเมลยืนยันอีกครั้ง'}
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="rounded-md text-sm text-gray-600 hover:text-gray-900 underline focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
                    >
                        ออกจากระบบ
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
