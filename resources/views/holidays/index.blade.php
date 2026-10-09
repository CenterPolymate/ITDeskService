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

                    <div class="mt-6 border-t pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">หรือ ดึงวันหยุดจากปฏิทินไทย (Google Calendar)</h4>
                        <form action="{{ route('holidays.index') }}" method="GET" class="flex gap-4 items-end flex-wrap">
                            <div>
                                <label for="fetch_year" class="block text-xs font-medium text-gray-500">เลือกปี</label>
                                <select name="fetch_year" id="fetch_year" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @php $currentYear = date('Y'); @endphp
                                    @for($i = $currentYear - 1; $i <= $currentYear + 2; $i++)
                                        <option value="{{ $i }}" {{ request('fetch_year', $currentYear) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 shadow-sm h-[38px]">
                                ดึงข้อมูล
                            </button>
                            @if(request()->has('fetch_year'))
                                <a href="{{ route('holidays.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 shadow-sm h-[38px]">
                                    ยกเลิก
                                </a>
                            @endif
                        </form>
                    </div>

                    @if(isset($suggestedHolidays) && count($suggestedHolidays) > 0)
                    <div class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="text-md font-medium text-gray-900 mb-3">เลือกวันหยุดที่ต้องการเพิ่มเข้าสู่ระบบ (ปี {{ request('fetch_year') }})</h4>
                        <form action="{{ route('holidays.storeBulk') }}" method="POST">
                            @csrf
                            <div class="max-h-60 overflow-y-auto mb-4 border border-gray-300 rounded bg-white p-2">
                                @foreach($suggestedHolidays as $index => $sh)
                                    <div class="flex items-center py-2 border-b border-gray-100 last:border-0">
                                        <input type="checkbox" name="holidays[{{ $index }}][date]" value="{{ $sh['date'] }}" id="sh_{{ $index }}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" checked>
                                        <input type="hidden" name="holidays[{{ $index }}][name]" value="{{ $sh['name'] }}">
                                        <label for="sh_{{ $index }}" class="ml-3 block text-sm text-gray-900 cursor-pointer">
                                            <span class="font-medium">{{ \Carbon\Carbon::parse($sh['date'])->translatedFormat('d F Y') }}</span> - {{ $sh['name'] }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 shadow-sm">
                                บันทึกวันหยุดที่เลือก
                            </button>
                        </form>
                    </div>
                    @elseif(request()->has('fetch_year'))
                    <div class="mt-4 p-3 bg-yellow-50 text-yellow-700 rounded text-sm border border-yellow-200">
                        ไม่มีรายการวันหยุดใหม่ที่สามารถเพิ่มได้ในปี {{ request('fetch_year') }} (อาจจะถูกเพิ่มไปหมดแล้ว หรือไม่มีข้อมูลในระบบ Google)
                    </div>
                    @endif
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
