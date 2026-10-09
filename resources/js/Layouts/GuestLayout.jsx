import { Link } from '@inertiajs/react';

export default function GuestLayout({ children, isRegister }) {
    return (
        <div className="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative overflow-hidden bg-animated-gradient">
            {/* Decorative circles */}
            <div className="absolute top-0 left-0 w-64 h-64 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-3xl transform -translate-x-1/2 -translate-y-1/2"></div>
            <div className="absolute bottom-0 right-0 w-96 h-96 bg-indigo-400 opacity-20 rounded-full mix-blend-overlay filter blur-3xl transform translate-x-1/3 translate-y-1/3"></div>

            <div className="relative z-10 w-full sm:max-w-md mt-6 px-8 py-10 glassmorphism shadow-2xl overflow-hidden sm:rounded-2xl">
                <div className="flex justify-center mb-6">
                    <Link href="/">
                        {/* We use a simple placeholder logo or SVG here since x-application-logo was used */}
                        <div className="h-24 w-auto flex items-center justify-center text-4xl font-bold text-indigo-600 drop-shadow-md">
                            IT
                        </div>
                    </Link>
                </div>
                
                <h2 className="text-center text-2xl font-bold text-gray-800 mb-2">ITDeskService</h2>
                <p className="text-center text-sm text-gray-500 mb-8">
                    {isRegister ? 'สมัครสมาชิกเพื่อเข้าใช้งานระบบ' : 'เข้าสู่ระบบเพื่อดำเนินการต่อ'}
                </p>

                {children}
            </div>
            <style jsx="true">{`
                .bg-animated-gradient {
                    background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
                    background-size: 400% 400%;
                    animation: gradientBG 15s ease infinite;
                }
                @keyframes gradientBG {
                    0% { background-position: 0% 50%; }
                    50% { background-position: 100% 50%; }
                    100% { background-position: 0% 50%; }
                }
                .glassmorphism {
                    background: rgba(255, 255, 255, 0.85);
                    backdrop-filter: blur(16px);
                    -webkit-backdrop-filter: blur(16px);
                    border: 1px solid rgba(255, 255, 255, 0.3);
                }
            `}</style>
        </div>
    );
}
