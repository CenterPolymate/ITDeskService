<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Administrator Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Quick Stats Row 1 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- IT Users -->
                <a href="{{ route('users.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-transform hover:scale-105 hover:border-indigo-300 hover:shadow-md cursor-pointer group">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1 group-hover:text-indigo-600 transition-colors">ผู้ใช้งานทีม IT (IT Staff)</p>
                        <h3 class="text-3xl font-black text-indigo-600">{{ number_format($stats['total_it_users']) }}</h3>
                        <p class="text-xs text-green-600 mt-2 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                            Active: {{ number_format($stats['active_it_users']) }}
                        </p>
                    </div>
                    <div class="p-4 bg-indigo-50 rounded-lg text-indigo-500 group-hover:bg-indigo-100 transition-colors">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                </a>

                <!-- Normal Users -->
                <a href="{{ route('normal_users.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-transform hover:scale-105 hover:border-blue-300 hover:shadow-md cursor-pointer group">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1 group-hover:text-blue-600 transition-colors">ผู้ใช้งานทั่วไป (Users)</p>
                        <h3 class="text-3xl font-black text-blue-600">{{ number_format($stats['total_normal_users']) }}</h3>
                        <p class="text-xs text-green-600 mt-2 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                            Active: {{ number_format($stats['active_normal_users']) }}
                        </p>
                    </div>
                    <div class="p-4 bg-blue-50 rounded-lg text-blue-500 group-hover:bg-blue-100 transition-colors">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                </a>

                <!-- Companies -->
                <a href="{{ route('companies.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-transform hover:scale-105 hover:border-teal-300 hover:shadow-md cursor-pointer group">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1 group-hover:text-teal-600 transition-colors">บริษัทในระบบ (Companies)</p>
                        <h3 class="text-3xl font-black text-teal-600">{{ number_format($stats['total_companies']) }}</h3>
                        <p class="text-xs text-green-600 mt-2 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                            Active: {{ number_format($stats['active_companies']) }}
                        </p>
                    </div>
                    <div class="p-4 bg-teal-50 rounded-lg text-teal-500 group-hover:bg-teal-100 transition-colors">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                </a>
            </div>

            <!-- Charts & Analytics Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Ticket Status Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:col-span-2">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-800">ปริมาณใบงานตามสถานะ (Ticket Distribution)</h3>
                    </div>
                    <div class="relative h-[300px] w-full flex justify-center items-center">
                        <canvas id="ticketStatusChart"></canvas>
                    </div>
                </div>

                <!-- System Health & Ticket Summary -->
                <div class="space-y-6">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-sm p-6 text-white relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 bg-white opacity-10 w-40 h-40 rounded-full blur-2xl"></div>
                        <h3 class="text-lg font-bold mb-1 relative z-10">อัตราการแก้ไขปัญหา</h3>
                        <p class="text-emerald-100 text-sm mb-4 relative z-10">Resolution Rate</p>
                        
                        <div class="flex items-end gap-2 relative z-10">
                            <span class="text-5xl font-black">{{ $stats['resolution_rate'] }}<span class="text-3xl">%</span></span>
                        </div>
                        
                        <div class="mt-6 space-y-2 relative z-10">
                            <div class="flex justify-between text-sm">
                                <span>ใบงานทั้งหมด</span>
                                <span class="font-bold">{{ number_format($stats['total_tickets']) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span>แก้ไขแล้วเสร็จ</span>
                                <span class="font-bold">{{ number_format($stats['resolved_tickets']) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-xl shadow-sm p-6 text-white relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 bg-white opacity-10 w-40 h-40 rounded-full blur-2xl"></div>
                        <h3 class="text-lg font-bold mb-1 relative z-10">ซ่อมเสร็จเกิน SLA</h3>
                        <p class="text-rose-100 text-sm mb-4 relative z-10">Breached SLA Tickets</p>
                        
                        <div class="flex items-end gap-2 relative z-10">
                            <span class="text-5xl font-black">{{ number_format($stats['breached_tickets']) }}<span class="text-xl font-medium ml-2">ใบ</span></span>
                        </div>
                        
                        <div class="mt-6 space-y-2 relative z-10">
                            <div class="flex justify-between text-sm">
                                <span>คิดเป็นสัดส่วน</span>
                                <span class="font-bold">{{ $stats['total_tickets'] > 0 ? round(($stats['breached_tickets'] / $stats['total_tickets']) * 100) : 0 }}%</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('backups.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6 hover:shadow-md hover:border-blue-300 transition-colors group cursor-pointer">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-600 transition-colors">ระบบสำรองข้อมูล (Backup)</h3>
                            <div class="p-2 bg-blue-50 text-blue-500 rounded-lg group-hover:bg-blue-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                            </div>
                        </div>
                        
                        @if($latestBackup)
                            <div class="flex items-center gap-2 mb-2">
                                <span class="relative flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                </span>
                                <span class="text-sm font-medium text-gray-700">สำรองข้อมูลล่าสุดเมื่อ:</span>
                            </div>
                            <p class="text-gray-600 text-sm mb-1 font-semibold">{{ $latestBackup['last_modified'] }}</p>
                            <p class="text-xs text-gray-400">ขนาด: {{ $latestBackup['size'] }}</p>
                        @else
                            <div class="flex items-center gap-2 mb-2">
                                <span class="relative flex h-3 w-3">
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-gray-300"></span>
                                </span>
                                <span class="text-sm font-medium text-gray-500">ยังไม่มีประวัติการสำรองข้อมูล</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">ระบบจะทำการสำรองข้อมูลอัตโนมัติทุกๆ เที่ยงคืน</p>
                        @endif
                    </a>

                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('ticketStatusChart').getContext('2d');
            
            const labels = @json($chartLabels);
            const data = @json($chartData);
            
            // Check if there's any data to display
            const sum = data.reduce((a, b) => a + b, 0);
            
            if (sum === 0) {
                // Display empty state if no tickets
                document.getElementById('ticketStatusChart').outerHTML = `
                    <div class="flex flex-col items-center justify-center text-gray-400 h-full">
                        <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        <p class="text-sm font-medium">ยังไม่มีข้อมูลใบงานในระบบ</p>
                    </div>
                `;
                return;
            }

            const chartColors = [
                '#64748b', // pending - slate-500
                '#a855f7', // analyzing - purple-500
                '#6366f1', // in_progress - indigo-500
                '#f59e0b', // resolved - amber-500
                '#f97316', // approved - orange-500
                '#10b981', // closed - emerald-500
                '#9ca3af'  // cancelled - gray-400
            ];

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: chartColors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    family: "'Sarabun', 'Inter', sans-serif"
                                },
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const percentage = Math.round((value / sum) * 100);
                                    return ` ${context.label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        });
    </script>
</x-app-layout>
