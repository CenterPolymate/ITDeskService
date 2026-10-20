import React from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function GuestLayout({ children, isRegister }) {
    const { branding } = usePage().props;
    const logoSrc = branding?.app_logo_login || branding?.app_logo || '/images/polymate-logo.png';
    const appName = branding?.app_name || 'ITDeskService';

    return (
        <div className="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative overflow-hidden bg-animated-gradient">
            {/* Decorative circles */}
            <div className="absolute top-0 left-0 w-64 h-64 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-3xl transform -translate-x-1/2 -translate-y-1/2"></div>
            <div className="absolute bottom-0 right-0 w-96 h-96 bg-indigo-400 opacity-20 rounded-full mix-blend-overlay filter blur-3xl transform translate-x-1/3 translate-y-1/3"></div>

            <div className="relative z-10 w-full sm:max-w-md mt-6 px-8 py-10 glassmorphism shadow-2xl overflow-hidden sm:rounded-2xl">
                <div className="flex justify-center mb-6">
                    <Link href="/">
                        <img src={logoSrc} alt={appName} className="h-20 max-w-[240px] w-auto object-contain drop-shadow-md" />
                    </Link>
                </div>
                
                <h2 className="text-center text-2xl font-bold text-gray-800 mb-2">{appName}</h2>
                <p className="text-center text-sm text-gray-500 mb-8">
                    {isRegister ? 'สมัครสมาชิกเพื่อเข้าใช้งานระบบ' : 'เข้าสู่ระบบเพื่อดำเนินการต่อ'}
                </p>

                {children}

                {branding?.support_contact && (
                    <div className="mt-8 pt-4 border-t border-gray-200/60 text-center">
                        <p className="text-xs text-gray-500 flex items-center justify-center gap-1.5 flex-wrap">
                            <span>📞 ติดต่อฝ่ายบริการ:</span>
                            <span className="font-semibold text-gray-700">{branding.support_contact}</span>
                        </p>
                    </div>
                )}
            </div>

        </div>
    );
}
