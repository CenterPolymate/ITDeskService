<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Administrator Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Quick Stats Row 1 -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- IT Users -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-transform hover:scale-105">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">ผู้ใช้งานทีม IT (IT Staff)</p>
                        <h3 class="text-3xl font-black text-indigo-600">{{ number_format($stats['total_it_users']) }}</h3>
                        <p class="text-xs text-green-600 mt-2 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                            Active: {{ number_format($stats['active_it_users']) }}
                        </p>
                    </div>
                    <div class="p-4 bg-indigo-50 rounded-lg text-indigo-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                </div>

                <!-- Normal Users -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-transform hover:scale-105">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">ผู้ใช้งานทั่วไป (Normal Users)</p>
                        <h3 class="text-3xl font-black text-blue-600">{{ number_format($stats['total_normal_users']) }}</h3>
                        <p class="text-xs text-green-600 mt-2 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                            Active: {{ number_format($stats['active_normal_users']) }}
                        </p>
                    </div>
                    <div class="p-4 bg-blue-50 rounded-lg text-blue-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                </div>

                <!-- Companies -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-transform hover:scale-105">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">บริษัทในระบบ (Companies)</p>
                        <h3 class="text-3xl font-black text-teal-600">{{ number_format($stats['total_companies']) }}</h3>
                        <p class="text-xs text-green-600 mt-2 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                            Active: {{ number_format($stats['active_companies']) }}
                        </p>
                    </div>
                    <div class="p-4 bg-teal-50 rounded-lg text-teal-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                </div>

                <!-- SLA Configurations -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-transform hover:scale-105">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">การตั้งค่า SLA (Rules)</p>
                        <h3 class="text-3xl font-black text-amber-600">{{ number_format($stats['total_slas']) }}</h3>
                        <p class="text-xs text-gray-500 mt-2 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full bg-gray-400 mr-1"></span>
                            Active Configurations
                        </p>
                    </div>
                    <div class="p-4 bg-amber-50 rounded-lg text-amber-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
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
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">การจัดการระบบ (Quick Actions)</h3>
                        <div class="space-y-3">
                            <a href="{{ route('users.index') }}" class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:bg-gray-50 hover:border-indigo-200 transition-colors group">
                                <div class="flex items-center gap-3">
                                    <div class="bg-indigo-100 text-indigo-600 p-2 rounded-md group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </div>
                                    <span class="font-medium text-gray-700">จัดการ IT Staff</span>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            <a href="{{ route('companies.index') }}" class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:bg-gray-50 hover:border-teal-200 transition-colors group">
                                <div class="flex items-center gap-3">
                                    <div class="bg-teal-100 text-teal-600 p-2 rounded-md group-hover:bg-teal-600 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    </div>
                                    <span class="font-medium text-gray-700">จัดการบริษัท</span>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-teal-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>
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
                '#f59e0b', // pending - yellow
                '#8b5cf6', // analyzing - purple
                '#3b82f6', // in_progress - blue
                '#10b981', // resolved - green
                '#f59e0b', // approved - yellow
                '#6b7280', // closed - gray
                '#ef4444'  // cancelled - red
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
