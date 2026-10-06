<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ระบบสำรองข้อมูล (Backup)') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif
            
            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if (session('backup_output'))
                <div class="mb-4 bg-gray-900 border border-gray-700 text-gray-300 px-4 py-3 rounded relative">
                    <div class="font-bold mb-2">Terminal Output:</div>
                    <pre class="text-xs overflow-x-auto whitespace-pre-wrap">{{ session('backup_output') }}</pre>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">สร้างไฟล์สำรองข้อมูล (Create Backup)</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        คุณสามารถสำรองข้อมูลระบบแบบเต็ม (Full Backup) ซึ่งจะรวมทั้งฐานข้อมูลและไฟล์แนบ หรือสำรองเฉพาะฐานข้อมูล (Database Only) เพื่อประหยัดพื้นที่ได้
                    </p>
                    
                    <form action="{{ route('backups.store') }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4" onsubmit="return confirm('การสำรองข้อมูลอาจใช้เวลาสักครู่ คุณต้องการดำเนินการต่อหรือไม่?');">
                        @csrf
                        <select name="type" class="w-full sm:w-auto border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="db_only">สำรองเฉพาะฐานข้อมูล (แนะนำ)</option>
                            <option value="full">สำรองทั้งหมด (ฐานข้อมูล + ไฟล์รูปภาพแนบ)</option>
                        </select>
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            เริ่มสำรองข้อมูล
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">ประวัติการสำรองข้อมูล (Backup History)</h3>
                    
                    @if(count($backups) > 0)
                        <!-- Mobile Card View -->
                        <div class="md:hidden space-y-4">
                            @foreach($backups as $backup)
                            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="text-sm font-semibold text-gray-900 break-all">{{ $backup['file_name'] }}</h4>
                                </div>
                                <div class="text-sm text-gray-600 space-y-1 mb-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">ขนาด:</span>
                                        <span>{{ $backup['file_size'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">เวลาที่สำรอง:</span>
                                        <span>{{ $backup['last_modified'] }}</span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('backups.download', ['file_name' => $backup['file_name']]) }}" class="flex-1 text-center text-indigo-600 hover:text-indigo-900 font-medium transition-colors px-3 py-2 bg-indigo-50 hover:bg-indigo-100 rounded-md text-sm">
                                        ดาวน์โหลด
                                    </a>
                                    <form action="{{ route('backups.destroy') }}" method="POST" class="flex-1 m-0 p-0" onsubmit="return confirm('คุณต้องการลบไฟล์สำรองข้อมูลนี้ใช่หรือไม่?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="file_name" value="{{ $backup['file_name'] }}">
                                        <button type="submit" class="w-full text-red-600 hover:text-red-900 font-medium transition-colors px-3 py-2 bg-red-50 hover:bg-red-100 rounded-md text-sm">
                                            ลบ
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Desktop Table View -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อไฟล์</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ขนาด</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">เวลาที่สำรอง</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($backups as $backup)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">{{ $backup['file_name'] }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ $backup['file_size'] }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ $backup['last_modified'] }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('backups.download', ['file_name' => $backup['file_name']]) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors px-2 py-1 bg-indigo-50 hover:bg-indigo-100 rounded-md">
                                                    ดาวน์โหลด
                                                </a>
                                                <form action="{{ route('backups.destroy') }}" method="POST" class="inline m-0 p-0" onsubmit="return confirm('คุณต้องการลบไฟล์สำรองข้อมูลนี้ใช่หรือไม่?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="file_name" value="{{ $backup['file_name'] }}">
                                                    <button type="submit" class="text-red-600 hover:text-red-900 transition-colors px-2 py-1 bg-red-50 hover:bg-red-100 rounded-md">
                                                        ลบ
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            ยังไม่มีประวัติการสำรองข้อมูล
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 min-w-0 flex-1">
                        <h3 class="text-sm font-medium text-blue-800">คำแนะนำการตั้งค่าสำรองข้อมูลอัตโนมัติ (Automated Backup)</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>กรุณาเพิ่มคำสั่ง <strong>Cron Job</strong> ในระบบจัดการเซิร์ฟเวอร์ (เช่น CloudPanel) โดยกำหนดระยะเวลาการทำงานเป็นทุก 1 นาที (<code>* * * * *</code>) ด้วยคำสั่งต่อไปนี้:</p>
                            <div class="overflow-x-auto mt-2 bg-blue-100 p-2 rounded">
                                <pre class="text-xs sm:text-sm"><code>* * * * * cd /home/htdocs/itdeskservice && php artisan schedule:run >> /dev/null 2>&1</code></pre>
                            </div>
                            <p class="mt-2 text-xs"><i>หมายเหตุ: คำสั่งดังกล่าวจะควบคุมให้ระบบทำการ <strong>สำรองข้อมูลทั้งหมด (Full Backup)</strong> โดยอัตโนมัติในเวลา 00:00 น. และลบไฟล์สำรองข้อมูลที่หมดอายุในเวลา 00:30 น. ของทุกวัน รวมถึงประมวลผลงานอัตโนมัติอื่นๆ ของแอปพลิเคชัน</i></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 min-w-0 flex-1">
                        <h3 class="text-sm font-medium text-yellow-800">คู่มือการกู้คืนข้อมูล (How to Restore)</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>เพื่อป้องกันปัญหาฐานข้อมูลเสียหายจาก Time-out ระหว่างการทำงานของเว็บไซต์ <strong>ระบบนี้จึงไม่มีปุ่ม Restore ผ่านหน้าเว็บโดยตรง</strong> หากเกิดเหตุฉุกเฉินและต้องการกู้คืนข้อมูล ให้ดำเนินการผ่านเซิร์ฟเวอร์ดังนี้:</p>
                            <ol class="list-decimal list-inside mt-2 space-y-1">
                                <li>กด <strong>"ดาวน์โหลด"</strong> ไฟล์ <code>.zip</code> จากตารางด้านบนไปไว้ที่เครื่องคอมพิวเตอร์ของคุณ</li>
                                <li>แตกไฟล์ <code>.zip</code> จะพบไฟล์ <code>.sql</code> ในโฟลเดอร์ <code>db-dumps</code> และไฟล์รูปภาพในโฟลเดอร์รูปภาพ</li>
                                <li>นำไฟล์ <code>.sql</code> ไป Import เข้าฐานข้อมูลเดิมผ่าน <strong>phpMyAdmin</strong> หรือเมนู Databases ใน CloudPanel</li>
                                <li>(ถ้าเป็นการกู้คืนรูปแบบเต็ม) ให้นำไฟล์รูปภาพทั้งหมด อัปโหลดไปวางทับในโฟลเดอร์ <code>/storage/app/public</code> ของระบบเซิร์ฟเวอร์</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
