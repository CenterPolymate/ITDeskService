<x-app-layout>
    <div class="py-4 sm:py-6">
        <div id="dashboard-content" class="w-full px-4 sm:px-6 lg:px-8 mx-auto xl:max-w-[95%]">
            
            @if (session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Waiting for Acceptance Banner -->
            @if($stats['waiting_acceptance'] > 0)
            <div class="mb-6 bg-gradient-to-r from-emerald-600 to-emerald-500 rounded-2xl p-5 shadow-lg flex flex-col sm:flex-row items-center justify-between text-white overflow-hidden relative border border-emerald-400">
                <div class="absolute right-0 top-0 w-48 h-48 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-2xl translate-x-12 -translate-y-12"></div>
                <div class="absolute left-0 bottom-0 w-32 h-32 bg-yellow-300 opacity-20 rounded-full mix-blend-overlay filter blur-xl -translate-x-10 translate-y-10"></div>
                <div class="flex items-center gap-5 z-10 w-full sm:w-auto mb-4 sm:mb-0">
                    <div class="bg-white/20 p-3 rounded-xl animate-bounce">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold tracking-wide">แจ้งเตือน: มีใบงานรอให้คุณตรวจสอบ</h3>
                        <p class="text-emerald-100 text-sm mt-1">ทีมงานได้ทำการแก้ไขใบงานของคุณเสร็จสิ้นแล้ว จำนวน <span class="font-black text-lg px-1">{{ number_format($stats['waiting_acceptance']) }}</span> รายการ กรุณาเข้าไปตรวจสอบและกดยืนยันรับงาน</p>
                    </div>
                </div>
                <div class="z-10 w-full sm:w-auto text-center">
                    <a href="?status=resolved#dashboard-content" class="inline-block w-full sm:w-auto bg-white text-emerald-600 font-bold py-2.5 px-6 rounded-xl shadow-md hover:bg-gray-50 hover:shadow-lg transform transition hover:-translate-y-0.5">
                        ดูใบงานที่ต้องยืนยัน
                    </a>
                </div>
            </div>
            @endif

            <!-- Welcome Banner & New Ticket Action -->
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">สวัสดี, {{ Auth::user()->name }}</h3>
                        <p class="mt-1 text-sm text-gray-600">ยินดีต้อนรับสู่ระบบแจ้งซ่อม ITDeskService คุณสามารถติดตามสถานะการแจ้งซ่อม หรือเปิดใบงานใหม่ได้ที่นี่</p>
                    </div>
                    <div>

                        <a href="{{ route('tickets.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                            + เปิดใบงานใหม่ (New Ticket)
                        </a>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <!-- Total Tickets -->
                <a href="?status=all#dashboard-content" class="bg-white shadow-sm rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-gray-200 hover:border-gray-300 hover:shadow-md transition-shadow h-32">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-gray-100 rounded-full mix-blend-multiply opacity-50 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-gray-800 mb-2 leading-tight">ใบงานทั้งหมด</p>
                    <div class="flex items-end justify-between mt-auto z-10">
                        <span class="text-4xl font-black text-gray-900 leading-none drop-shadow-sm">{{ number_format($stats['total']) }}</span>
                        <svg class="w-7 h-7 text-gray-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </a>

                <!-- Pending Tickets -->
                <a href="?status=user_pending#dashboard-content" class="bg-white shadow-sm rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-yellow-200 hover:border-yellow-300 hover:shadow-md transition-shadow h-32">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-yellow-100 rounded-full mix-blend-multiply opacity-50 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-yellow-800 mb-2 leading-tight">เปิดเคส / รอดำเนินการ</p>
                    <div class="flex items-end justify-between mt-auto z-10">
                        <span class="text-4xl font-black text-yellow-600 leading-none drop-shadow-sm">{{ number_format($stats['pending']) }}</span>
                        <svg class="w-7 h-7 text-yellow-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </a>

                <!-- In Progress Tickets -->
                <a href="?status=user_in_progress#dashboard-content" class="bg-white shadow-sm rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-blue-200 hover:border-blue-300 hover:shadow-md transition-shadow h-32">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-blue-100 rounded-full mix-blend-multiply opacity-50 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-blue-800 mb-2 leading-tight">กำลังดำเนินการ</p>
                    <div class="flex items-end justify-between mt-auto z-10">
                        <span class="text-4xl font-black text-blue-600 leading-none drop-shadow-sm">{{ number_format($stats['in_progress']) }}</span>
                        <svg class="w-7 h-7 text-blue-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                </a>

                <!-- Completed Tickets -->
                <a href="?status=user_completed#dashboard-content" class="bg-white shadow-sm rounded-2xl p-4 relative overflow-hidden flex flex-col justify-between border-2 border-green-200 hover:border-green-300 hover:shadow-md transition-shadow h-32">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-green-100 rounded-full mix-blend-multiply opacity-50 translate-x-8 -translate-y-8"></div>
                    <p class="text-sm font-bold text-green-800 mb-2 leading-tight">แก้ไขเสร็จ / ปิดใบงาน</p>
                    <div class="flex items-end justify-between mt-auto z-10">
                        <span class="text-4xl font-black text-green-600 leading-none drop-shadow-sm">{{ number_format($stats['completed']) }}</span>
                        <svg class="w-7 h-7 text-green-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </a>
            </div>

            <!-- Ticket List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                        <h3 class="text-lg font-medium text-gray-900">ประวัติการแจ้งซ่อมของคุณ</h3>
                        <form action="" method="GET" class="w-full sm:w-1/3">
                            <input type="hidden" name="status" value="{{ request('status') }}">
                            <div class="relative">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาใบงาน (หัวข้อ, Ticket No...)" class="w-full pl-10 pr-4 py-2 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 shadow-sm text-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    @if($cases->isEmpty())
                        <div class="text-center py-12 flex flex-col items-center">
                            <img src="{{ asset('images/empty-state.jpg') }}" alt="Empty State" class="w-48 h-48 object-contain mb-4 opacity-80 rounded-xl shadow-sm mix-blend-multiply">
                            <h3 class="mt-2 text-lg font-medium text-gray-900">ยังไม่มีใบงานในขณะนี้</h3>
                            <p class="mt-1 text-sm text-gray-500 max-w-sm">หน้ากระดานของคุณว่างเปล่า คุณสามารถเปิดใบงานแจ้งปัญหาได้ทันทีที่พบปัญหา</p>
                            <a href="{{ route('tickets.create') }}" class="mt-6 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                + เปิดใบงานใหม่
                            </a>
                        </div>
                    @else
                        <!-- Mobile Card View -->
                        <div class="md:hidden space-y-4">
                            @foreach($cases as $case)
                                <a href="{{ route('tickets.show', $case->id) }}" class="block bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-sm font-bold text-blue-600">#{{ $case->ticket_no }}</span>
                                        <span class="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full 
                                            {{ $case->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $case->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $case->status === 'resolved' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $case->status === 'closed' ? 'bg-gray-100 text-gray-800' : '' }}
                                        ">
                                            {{ $case->status_label }}
                                        </span>
                                    </div>
                                    <h4 class="text-base font-semibold text-gray-900 mb-1 leading-tight">{{ $case->title }}</h4>
                                    <div class="flex justify-between items-center text-xs text-gray-500 mt-3 pt-3 border-t border-gray-100">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                            {{ $case->category }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ $case->created_at->format('d/m/y H:i') }}
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        <!-- Desktop Table View -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket No</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หัวข้อปัญหา</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หมวดหมู่</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่แจ้ง</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($cases as $case)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('tickets.show', $case->id) }}" class="text-blue-600 hover:text-blue-900 hover:underline">
                                                    #{{ $case->ticket_no }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $case->title }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $case->category }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $case->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $case->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $case->status === 'resolved' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $case->status === 'closed' ? 'bg-gray-100 text-gray-800' : '' }}
                                                ">
                                                    {{ $case->status_label }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $case->created_at->translatedFormat('d F Y H:i') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('tickets.show', $case->id) }}" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-md transition-colors">ดูรายละเอียด</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $cases->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <!-- Live Monitor Polling Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setInterval(() => {
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
