<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ตั้งค่าวันหยุดนักขัตฤกษ์') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">เพิ่มวันหยุดนักขัตฤกษ์ (ส่งผลต่อการนับ SLA 8x5)</h3>
                    <form action="{{ route('holidays.store') }}" method="POST" class="flex gap-4 items-end flex-wrap">
                        @csrf
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">ชื่อวันหยุด <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" required class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="เช่น วันขึ้นปีใหม่">
                        </div>
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700">วันที่ <span class="text-red-500">*</span></label>
                            <input type="date" name="date" id="date" required class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 shadow-sm h-[38px]">
                            เพิ่มวันหยุด
                        </button>
                    </form>
                    @error('date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">รายการวันหยุดทั้งหมด</h3>
                    
                    @if($holidays->count() > 0)
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อวันหยุด</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($holidays as $holiday)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $holiday->date->translatedFormat('d F Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $holiday->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <form action="{{ route('holidays.destroy', $holiday->id) }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบวันหยุดนี้?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 transition-colors">ลบ</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-sm italic">ยังไม่มีข้อมูลวันหยุดในระบบ</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
