<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ระบบสำรองข้อมูล (Backup)') }}
        </h2>
    </x-slot>

    <div class="py-12">
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
                    
                    <form action="{{ route('backups.store') }}" method="POST" class="flex items-center gap-4" onsubmit="return confirm('การสำรองข้อมูลอาจใช้เวลาสักครู่ คุณต้องการดำเนินการต่อหรือไม่?');">
                        @csrf
                        <select name="type" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="db_only">สำรองเฉพาะฐานข้อมูล (แนะนำ)</option>
                            <option value="full">สำรองทั้งหมด (ฐานข้อมูล + ไฟล์รูปภาพแนบ)</option>
                        </select>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            เริ่มสำรองข้อมูล
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">ประวัติการสำรองข้อมูล (Backup History)</h3>
                    
                    @if(count($backups) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
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
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $backup['file_name'] }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $backup['file_size'] }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $backup['last_modified'] }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
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
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">คำแนะนำการตั้งค่าสำรองข้อมูลอัตโนมัติ (Automated Backup)</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>เพื่อให้ระบบสำรองข้อมูลทำงานอัตโนมัติทุกวัน คุณสามารถไปตั้งค่าที่ <strong>CloudPanel > Cron Jobs</strong> โดยเพิ่มคำสั่งดังนี้:</p>
                            <pre class="mt-2 bg-blue-100 p-2 rounded"><code>0 0 * * * cd /home/htdocs/itdeskservice && php artisan backup:run --only-db</code></pre>
                            <p class="mt-2"><i>(คำสั่งด้านบนคือการรัน Backup ฐานข้อมูลทุกๆ เที่ยงคืนของทุกวัน)</i></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
