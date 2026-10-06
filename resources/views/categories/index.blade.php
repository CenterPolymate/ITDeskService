<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('จัดการหมวดหมู่ปัญหา') }}
            </h2>
            <a href="{{ route('categories.create') }}" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                + เพิ่มหมวดหมู่ใหม่
            </a>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Mobile Card View -->
                    <div class="md:hidden space-y-4">
                        @foreach($categories as $category)
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                            <div class="flex justify-between items-start mb-2 border-b border-gray-100 pb-2">
                                <h4 class="text-base font-medium text-gray-900">{{ $category->name }}</h4>
                                @if($category->is_active)
                                    <span class="px-2 inline-flex text-[10px] leading-5 font-semibold rounded-full bg-green-100 text-green-800">เปิดใช้งาน</span>
                                @else
                                    <span class="px-2 inline-flex text-[10px] leading-5 font-semibold rounded-full bg-red-100 text-red-800">ปิดใช้งาน</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-600 mb-3">
                                <span class="font-medium text-gray-700">รายละเอียด:</span> {{ $category->description ?? '-' }}
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('categories.edit', $category->id) }}" class="flex-1 text-center text-indigo-600 hover:text-indigo-900 font-medium transition-colors px-3 py-2 bg-indigo-50 hover:bg-indigo-100 rounded-md text-sm">แก้ไข</a>
                                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="flex-1 m-0 p-0" onsubmit="return confirm('ยืนยันการลบหมวดหมู่นี้?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full text-red-600 hover:text-red-900 font-medium transition-colors px-3 py-2 bg-red-50 hover:bg-red-100 rounded-md text-sm">ลบ</button>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อหมวดหมู่</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">รายละเอียด</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($categories as $category)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $category->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $category->description ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($category->is_active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">เปิดใช้งาน</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">ปิดใช้งาน</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('categories.edit', $category->id) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors px-2 py-1 bg-indigo-50 hover:bg-indigo-100 rounded-md">แก้ไข</a>
                                            <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="inline m-0 p-0" onsubmit="return confirm('ยืนยันการลบหมวดหมู่นี้?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors px-2 py-1 bg-red-50 hover:bg-red-100 rounded-md">ลบ</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
