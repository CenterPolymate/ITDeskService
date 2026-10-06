<x-app-layout>
    <style>
        /* Glassmorphism utilities */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.12);
        }

        /* Subtle animated gradient background for the whole page */
        .bg-animated-gradient {
            background: linear-gradient(-45deg, #f3f4f6, #e5e7eb, #dbeafe, #f3e8ff);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Status pulses */
        .status-pulse {
            animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>

    <div class="bg-animated-gradient min-h-screen pb-12 pt-6">
        <div id="dashboard-content" class="w-full px-4 sm:px-6 lg:px-8 mx-auto xl:max-w-[95%]">

            <!-- Urgent Banner -->
            @if($stats['urgent'] > 0)
            <div class="mb-6 bg-gradient-to-r from-red-600 to-red-500 rounded-2xl p-5 shadow-lg flex flex-col sm:flex-row items-center justify-between text-white overflow-hidden relative border border-red-400">
                <div class="absolute right-0 top-0 w-48 h-48 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-2xl translate-x-12 -translate-y-12"></div>
                <div class="absolute left-0 bottom-0 w-32 h-32 bg-yellow-300 opacity-20 rounded-full mix-blend-overlay filter blur-xl -translate-x-10 translate-y-10"></div>
                <div class="flex items-center gap-5 z-10 w-full sm:w-auto mb-4 sm:mb-0">
                    <div class="bg-white/20 p-3 rounded-xl animate-bounce">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold tracking-wide">แจ้งเตือนด่วน! มีเคสที่ต้องจัดการทันที</h3>
                        <p class="text-red-100 text-sm mt-1">พบใบงานระดับความเร่งด่วน "ด่วนที่สุด" จำนวน <span class="font-black text-lg px-1">{{ number_format($stats['urgent']) }}</span> รายการที่รอการแก้ไข</p>
                    </div>
                </div>
                <div class="z-10 w-full sm:w-auto text-center">
                    <a href="?priority=urgent&status=not_closed#tickets-table" class="inline-block w-full sm:w-auto bg-white text-red-600 font-bold py-2.5 px-6 rounded-xl shadow-md hover:bg-gray-50 hover:shadow-lg transform transition hover:-translate-y-0.5">
                        ดูเคสด่วนทั้งหมด
                    </a>
                </div>
            </div>
            @endif

            <!-- Manager Approval Banner -->
            @if(Auth::user()->role === 'manager' && $stats['task2_manager_review'] > 0)
            <div class="mb-6 bg-gradient-to-r from-amber-600 to-amber-500 rounded-2xl p-5 shadow-lg flex flex-col sm:flex-row items-center justify-between text-white overflow-hidden relative border border-amber-400">
                <div class="absolute right-0 top-0 w-48 h-48 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-2xl translate-x-12 -translate-y-12"></div>
                <div class="absolute left-0 bottom-0 w-32 h-32 bg-yellow-300 opacity-20 rounded-full mix-blend-overlay filter blur-xl -translate-x-10 translate-y-10"></div>
                <div class="flex items-center gap-5 z-10 w-full sm:w-auto mb-4 sm:mb-0">
                    <div class="bg-white/20 p-3 rounded-xl animate-pulse">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold tracking-wide">แจ้งเตือน: งานรอหัวหน้าตรวจสอบ</h3>
                        <p class="text-amber-100 text-sm mt-1">พบใบงานสถานะ "ปิดใบงานแล้ว" จำนวน <span class="font-black text-lg px-1">{{ number_format($stats['task2_manager_review']) }}</span> รายการที่รอการตรวจสอบมาตรการป้องกัน</p>
                    </div>
                </div>
                <div class="z-10 w-full sm:w-auto text-center">
                    <a href="?status=task2_manager_review#tickets-table" class="inline-block w-full sm:w-auto bg-white text-amber-600 font-bold py-2.5 px-6 rounded-xl shadow-md hover:bg-gray-50 hover:shadow-lg transform transition hover:-translate-y-0.5">
                        ดูเคสรอตรวจสอบทั้งหมด
                    </a>
                </div>
            </div>
            @endif

            <!-- Task 1 Section Header -->
            <div class="mb-4 flex items-center">
                <div class="bg-blue-600 w-1.5 h-6 rounded-full mr-3"></div>
                <h2 class="text-xl font-bold text-gray-800">Task 1: สถานะการแจ้งซ่อม (Ticketing)</h2>
            </div>
            
            <!-- Task 1 KPI Cards Row -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8 auto-rows-fr">
                <!-- Total Cases -->
                <a href="?status=all#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-blue-200 hover:border-blue-300">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-blue-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-blue-800 mb-2 leading-tight">เคสทั้งหมด</p>
                    <div class="flex items-end justify-between mt-auto">
                        <span class="text-4xl font-black text-gray-900 leading-none drop-shadow-sm">{{ number_format($stats['total']) }}</span>
                        <svg class="w-7 h-7 text-blue-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                </a>

                <!-- Pending Cases -->
                <a href="?status=pending#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-yellow-200 hover:border-yellow-300">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-yellow-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-yellow-800 mb-2 leading-tight">รอดำเนินการ / มอบหมายแล้ว</p>
                    <div class="flex items-end justify-between mt-auto">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-yellow-600 leading-none drop-shadow-sm">{{ number_format($stats['pending']) }}</span>
                            <span class="text-sm font-bold text-gray-400 bg-white/60 px-2 rounded-md">{{ $stats['percent_pending'] }}%</span>
                        </div>
                        <svg class="w-7 h-7 text-yellow-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"></path></svg>
                    </div>
                </a>

                <!-- Analyzing Cases -->
                <a href="?status=analyzing#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-purple-200 hover:border-purple-300">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-purple-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-purple-800 mb-2 leading-tight">สืบสภาพ และวิเคราะห์หาสาเหตุ</p>
                    <div class="flex items-end justify-between mt-auto">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-purple-600 leading-none drop-shadow-sm">{{ number_format($stats['analyzing']) }}</span>
                            <span class="text-sm font-bold text-gray-400 bg-white/60 px-2 rounded-md">{{ $stats['percent_analyzing'] }}%</span>
                        </div>
                        <svg class="w-7 h-7 text-purple-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </a>

                <!-- In Progress Cases -->
                <a href="?status=in_progress#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-indigo-200 hover:border-indigo-300">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-indigo-800 mb-2 leading-tight">ดำเนินการแก้ไข</p>
                    <div class="flex items-end justify-between mt-auto">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-indigo-600 leading-none drop-shadow-sm">{{ number_format($stats['in_progress']) }}</span>
                            <span class="text-sm font-bold text-gray-400 bg-white/60 px-2 rounded-md">{{ $stats['percent_in_progress'] }}%</span>
                        </div>
                        <svg class="w-7 h-7 text-indigo-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                </a>

                <!-- Testing & Delivery Cases -->
                <a href="?status=testing#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-emerald-200 hover:border-emerald-300">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-emerald-800 mb-2 leading-tight">ทดสอบและส่งมอบ</p>
                    <div class="flex items-end justify-between mt-auto">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-emerald-600 leading-none drop-shadow-sm">{{ number_format($stats['testing']) }}</span>
                            <span class="text-sm font-bold text-gray-400 bg-white/60 px-2 rounded-md">{{ $stats['percent_testing'] }}%</span>
                        </div>
                        <svg class="w-7 h-7 text-emerald-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    </div>
                </a>



                <!-- Completed Cases (Closed + Cancelled) -->
                <a href="?status=completed#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-emerald-200 hover:border-emerald-300">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-emerald-800 mb-2 leading-tight">แก้ไขเสร็จ / ปิดใบงาน</p>
                    <div class="flex items-end justify-between mt-auto">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-emerald-600 leading-none drop-shadow-sm">{{ number_format($stats['completed']) }}</span>
                            <span class="text-sm font-bold text-gray-400 bg-white/60 px-2 rounded-md">{{ $stats['percent_completed'] }}%</span>
                        </div>
                        <div class="flex flex-col gap-1 items-end">
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100/80 px-1.5 py-0.5 rounded flex items-center gap-1">ปิดงาน {{ number_format($stats['closed']) }} <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span></span>
                            <span class="text-[10px] font-bold text-red-700 bg-red-100/80 px-1.5 py-0.5 rounded flex items-center gap-1">ยกเลิก {{ number_format($stats['cancelled']) }} <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span></span>
                        </div>
                    </div>
                </a>
            </div>

            @if(Auth::user()->role !== 'helpdesk')
            <!-- Task 2 Section Header -->
            <div class="mb-4 mt-2 flex items-center">
                <div class="bg-teal-600 w-1.5 h-6 rounded-full mr-3"></div>
                <h2 class="text-xl font-bold text-gray-800">Task 2: การป้องกันปัญหาไม่ให้เกิดซ้ำ (Preventive Action)</h2>
            </div>

            <!-- Task 2 KPI Cards Row -->
            <div class="grid grid-cols-2 md:grid-cols-3 {{ in_array(Auth::user()->role, ['manager', 'administrator']) ? 'lg:grid-cols-6' : 'lg:grid-cols-5' }} gap-4 mb-8 auto-rows-fr">

                <!-- 1. เคสทั้งหมด (Task 2 All Cases) -->
                <a href="?status=task2_all#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-indigo-200 hover:border-indigo-300">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-indigo-800 mb-2 leading-tight">เคสทั้งหมด</p>
                    <div class="flex items-end justify-between mt-auto">
                        <span class="text-4xl font-black text-indigo-600 leading-none drop-shadow-sm">{{ number_format($stats['task2_total']) }}</span>
                        <svg class="w-7 h-7 text-indigo-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                </a>

                <!-- 2. รอหัวหน้าตรวจสอบ (Manager Review Cases) -->
                @if(in_array(Auth::user()->role, ['manager', 'administrator']))
                <a href="?status=task2_manager_review#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-orange-200 hover:border-orange-300">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-orange-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-orange-800 mb-2 leading-tight">รอหัวหน้าตรวจสอบ</p>
                    <div class="flex items-end justify-between mt-auto">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-orange-600 leading-none drop-shadow-sm">{{ number_format($stats['task2_manager_review']) }}</span>
                            <span class="text-sm font-bold text-gray-400 bg-white/60 px-2 rounded-md">{{ $stats['percent_t2_manager'] }}%</span>
                        </div>
                        <svg class="w-7 h-7 text-orange-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                </a>
                @endif
                
                <!-- 2.5 รอดำเนินการ / มอบหมายแล้ว (Assigned / Pending) -->
                <a href="?status=task2_assigned#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-rose-200 hover:border-rose-300">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-rose-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-rose-800 mb-2 leading-tight">รอดำเนินการ / มอบหมายแล้ว</p>
                    <div class="flex items-end justify-between mt-auto">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-rose-600 leading-none drop-shadow-sm">{{ number_format($stats['task2_assigned']) }}</span>
                            <span class="text-sm font-bold text-gray-400 bg-white/60 px-2 rounded-md">{{ $stats['percent_t2_assigned'] }}%</span>
                        </div>
                        <svg class="w-7 h-7 text-rose-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </a>

                <!-- 3. กำลังดำเนินการป้องกัน (In Progress) -->
                <a href="?status=task2_in_progress#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-yellow-300 hover:border-yellow-400">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-yellow-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-yellow-900 mb-2 leading-tight">กำลังดำเนินการป้องกัน</p>
                    <div class="flex items-end justify-between mt-auto">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-yellow-700 leading-none drop-shadow-sm">{{ number_format($stats['task2_in_progress']) }}</span>
                            <span class="text-sm font-bold text-gray-400 bg-white/60 px-2 rounded-md">{{ $stats['percent_t2_in_progress'] }}%</span>
                        </div>
                        <svg class="w-7 h-7 text-yellow-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                </a>

                <!-- 4. รอตรวจปิดมาตรการป้องกัน (Pending Manager Review) -->
                <a href="?status=task2_pending_review#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-blue-200 hover:border-blue-300">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-blue-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-blue-800 mb-2 leading-tight">กำลังตรวจสอบปิดมาตรการป้องกัน</p>
                    <div class="flex items-end justify-between mt-auto">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-blue-600 leading-none drop-shadow-sm">{{ number_format($stats['task2_pending_review']) }}</span>
                            <span class="text-sm font-bold text-gray-400 bg-white/60 px-2 rounded-md">{{ $stats['percent_t2_pending'] }}%</span>
                        </div>
                        <svg class="w-7 h-7 text-blue-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </a>

                <!-- 5. ปิดมาตรการป้องกัน (Completed) -->
                <a href="?status=task2_completed#tickets-table" class="glass-card rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-teal-200 hover:border-teal-300">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-teal-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-teal-800 mb-2 leading-tight">ปิดมาตรการป้องกัน</p>
                    <div class="flex items-end justify-between mt-auto">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-teal-600 leading-none drop-shadow-sm">{{ number_format($stats['task2_completed']) }}</span>
                            <span class="text-sm font-bold text-gray-400 bg-white/60 px-2 rounded-md">{{ $stats['percent_t2_completed'] }}%</span>
                        </div>
                        <svg class="w-7 h-7 text-teal-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                </a>
            </div>
            @endif

            <!-- Table Section -->
            <div id="tickets-table" class="glass-card rounded-2xl overflow-hidden mt-6 scroll-mt-6">
                <div class="px-6 py-5 border-b border-gray-200 border-opacity-50 bg-white/40">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">รายการแจ้งซ่อมที่ต้องจัดการ</h3>
                        </div>
                    </div>

                    <!-- Comprehensive Filters -->
                    <form action="{{ route('dashboard') }}" method="GET" class="w-full mt-4 p-4 bg-white/50 rounded-xl border border-white shadow-sm">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">สถานะ (Status)</label>
                                <select name="status" class="w-full rounded-lg border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white">
                                    <option value="all">ทุกสถานะ</option>
                                    <option value="not_closed" {{ request('status') === 'not_closed' ? 'selected' : '' }}>ยังไม่ปิดใบงาน</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>กำลังทำ (Assigned/Analyzing/In Progress)</option>
                                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>รอผู้แจ้งรับงาน</option>
                                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>รอหัวหน้าปิดใบงาน</option>
                                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>แก้ไขเสร็จ / ปิดใบงาน</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">ความเร่งด่วน (Priority)</label>
                                <select name="priority" class="w-full rounded-lg border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white">
                                    <option value="all">ทุกระดับ</option>
                                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>ด่วนที่สุด (Urgent)</option>
                                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>สูง (High)</option>
                                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>ปานกลาง (Medium)</option>
                                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>ทั่วไป (Low)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">ทีม (Team)</label>
                                <select name="team" class="w-full rounded-lg border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white">
                                    <option value="all">ทุกทีม</option>
                                    <option value="Hardware" {{ request('team') === 'Hardware' ? 'selected' : '' }}>ทีม Hardware</option>
                                    <option value="Network" {{ request('team') === 'Network' ? 'selected' : '' }}>ทีม Network</option>
                                    <option value="Software" {{ request('team') === 'Software' ? 'selected' : '' }}>ทีม Software</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">ค้นหา (Search)</label>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหา Ticket, ผู้แจ้ง..." class="w-full rounded-lg border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white">
                            </div>
                            
                            <div class="flex items-end gap-2">
                                <button type="submit" class="w-full bg-indigo-600 border border-transparent text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                                    กรองข้อมูล
                                </button>
                                <a href="{{ route('dashboard') }}" class="w-full text-center bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors shadow-sm">
                                    ล้างค่า
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Mobile Card View -->
                <div class="md:hidden space-y-4 p-4">
                    @forelse($cases as $case)
                        <a href="{{ route('tickets.show', $case->id) }}" class="block glass-card border border-white/40 rounded-xl shadow-sm p-4 relative overflow-hidden">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-sm font-bold text-indigo-600">#{{ $case->ticket_no }}</span>
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'analyzing' => 'bg-purple-100 text-purple-700',
                                        'in_progress' => 'bg-blue-100 text-blue-700',
                                        'resolved' => 'bg-cyan-100 text-cyan-700',
                                        'approved' => 'bg-amber-100 text-amber-700',
                                        'closed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                    ];
                                    $sColor = $statusColors[$case->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <div class="flex flex-col items-end">
                                    <span class="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full shadow-sm {{ $sColor }}">
                                        {{ $case->status_label }}
                                    </span>
                                    @if($case->task2_status_label)
                                    <span class="mt-1 px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full shadow-sm {{ $case->task2_status_color }}">
                                        {{ $case->task2_status_label }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <h4 class="text-base font-semibold text-gray-900 mb-1 leading-tight">{{ $case->title }}</h4>
                            <div class="text-xs text-gray-500 mb-3">โดย: {{ $case->requester_name }} ({{ $case->department }})</div>
                            
                            <div class="flex justify-between items-center text-xs mt-3 pt-3 border-t border-gray-200/50">
                                @php
                                    $priorityColors = [
                                        'urgent' => 'text-red-700 font-bold',
                                        'high' => 'text-orange-700 font-bold',
                                        'medium' => 'text-yellow-700 font-medium',
                                        'low' => 'text-blue-700',
                                    ];
                                    $pColorText = $priorityColors[$case->priority] ?? 'text-gray-600';
                                @endphp
                                <span class="{{ $pColorText }}">{{ $case->priority_label }}</span>
                                <span class="flex items-center gap-1 text-gray-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ $case->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-8">
                            <svg class="h-10 w-10 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p class="text-sm font-medium text-gray-500">ไม่พบใบงานแจ้งซ่อมในขณะนี้</p>
                        </div>
                    @endforelse
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200/60">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ticket No</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ข้อมูลผู้แจ้ง & ปัญหา</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ความเร่งด่วน</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ทีมที่รับผิดชอบ</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">สถานะ</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200/50 bg-white/30 backdrop-blur-sm">
                            @forelse($cases as $case)
                                <tr class="hover:bg-white/60 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('tickets.show', $case->id) }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-900 hover:underline">
                                            #{{ $case->ticket_no }}
                                        </a>
                                        <div class="text-xs text-gray-500 mt-1">{{ $case->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900">{{ $case->title }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            โดย: {{ $case->requester_name }} ({{ $case->department }})
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $priorityColors = [
                                                'urgent' => 'bg-red-100 text-red-800 border-red-200',
                                                'high' => 'bg-orange-100 text-orange-800 border-orange-200',
                                                'medium' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                'low' => 'bg-blue-100 text-blue-800 border-blue-200',
                                            ];
                                            $pColor = $priorityColors[$case->priority] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                        @endphp
                                        <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-md border {{ $pColor }}">
                                            {{ $case->priority_label }}
                                        </span>
                                    </td>
                                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                          @if($case->escalated_to_team)
                                              ทีม {{ $case->escalated_to_team }}
                                          @elseif($case->status === 'pending')
                                              <span class="text-gray-400 italic">ยังไม่ระบุ</span>
                                          @else
                                              Helpdesk (Tier 1)
                                          @endif
                                      </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-700',
                                                'analyzing' => 'bg-purple-100 text-purple-700',
                                                'in_progress' => 'bg-blue-100 text-blue-700',
                                                'resolved' => 'bg-cyan-100 text-cyan-700',
                                                'approved' => 'bg-amber-100 text-amber-700',
                                                'closed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-700',
                                            ];
                                            $sColor = $statusColors[$case->status] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full shadow-sm {{ $sColor }}">
                                            {{ $case->status_label }}
                                        </span>
                                        @if($case->task2_status_label)
                                        <div class="mt-1.5">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full shadow-sm {{ $case->task2_status_color }}">
                                                {{ $case->task2_status_label }}
                                            </span>
                                        </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('tickets.show', $case->id) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-md transition-colors">ดูรายละเอียด</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <p class="text-sm font-medium">ไม่พบใบงานแจ้งซ่อมในขณะนี้</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($cases->hasPages())
                <div class="px-6 py-4 border-t border-gray-200/50 bg-white/40">
                    {{ $cases->links() }}
                </div>
                @endif
            </div>

        </div>
    </div>

    <!-- Live Monitor Polling Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let isTyping = false;
            
            // Prevent refresh while user is interacting with filters
            const filterInputs = document.querySelectorAll('#tickets-table form input, #tickets-table form select');
            
            filterInputs.forEach(input => {
                input.addEventListener('focus', () => isTyping = true);
                input.addEventListener('blur', () => isTyping = false);
            });

            setInterval(() => {
                // Do not update if user is actively searching/filtering
                if (isTyping) return;
                
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        const currentContent = document.getElementById('dashboard-content');
                        const newContent = doc.getElementById('dashboard-content');
                        
                        if (currentContent && newContent) {
                            currentContent.innerHTML = newContent.innerHTML;
                        }
                    })
                    .catch(error => console.error('Error fetching live updates:', error));
            }, 10000); // 10 seconds
        });
    </script>
</x-app-layout>
