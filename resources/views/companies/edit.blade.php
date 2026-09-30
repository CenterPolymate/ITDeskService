<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('แก้ไขบริษัท') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">ข้อมูลบริษัท</h3>
                    <form action="{{ route('companies.update', $company->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">ชื่อบริษัท <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('name', $company->name) }}">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="short_name" class="block text-sm font-medium text-gray-700">ชื่อย่อ (ถ้ามี)</label>
                            <input type="text" name="short_name" id="short_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('short_name', $company->short_name) }}">
                            @error('short_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="email_domains" class="block text-sm font-medium text-gray-700">โดเมนอีเมลที่อนุญาต (คั่นด้วยลูกน้ำ) <span class="text-red-500">*</span></label>
                            <input type="text" name="email_domains" id="email_domains" required placeholder="เช่น @polymate.co.th, @gmail.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('email_domains', $company->email_domains) }}">
                            @error('email_domains')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" value="1" {{ old('is_active', $company->is_active) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-600">เปิดใช้งาน</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end mt-4 gap-3">
                            <a href="{{ route('companies.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                ยกเลิก
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                                บันทึกการแก้ไข
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Manage Departments Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">จัดการหน่วยงาน/แผนก (Departments)</h3>
                    
                    <!-- Add Department Form -->
                    <form action="{{ route('companies.departments.store', $company->id) }}" method="POST" class="mb-6 flex gap-3 items-end">
                        @csrf
                        <div class="flex-1">
                            <label for="dept_name" class="block text-sm font-medium text-gray-700 mb-1">เพิ่มหน่วยงานใหม่</label>
                            <input type="text" name="name" id="dept_name" required placeholder="เช่น IT, HR, จัดซื้อ" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-700 focus:bg-teal-700 active:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition shadow-sm h-[38px]">
                            เพิ่ม
                        </button>
                    </form>

                    <!-- List Departments -->
                    @if($company->departments->count() > 0)
                        <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                            <ul class="divide-y divide-gray-200">
                                @foreach($company->departments as $dept)
                                    <li x-data="{ editing: false }" class="p-4 flex items-center justify-between hover:bg-gray-100 transition-colors">
                                        <!-- View Mode -->
                                        <div x-show="!editing" class="flex-1 flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-800">{{ $dept->name }}</span>
                                            <div class="flex items-center gap-4">
                                                <button type="button" @click="editing = true" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition-colors">
                                                    แก้ไข
                                                </button>
                                                <form action="{{ route('companies.departments.destroy', $dept->id) }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบหน่วยงานนี้?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium transition-colors">
                                                        ลบ
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Edit Mode -->
                                        <div x-show="editing" style="display: none;" class="w-full">
                                            <form action="{{ route('companies.departments.update', $dept->id) }}" method="POST" class="flex gap-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="text" name="name" value="{{ $dept->name }}" required class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                <button type="submit" class="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">บันทึก</button>
                                                <button type="button" @click="editing = false" class="inline-flex items-center px-3 py-1 bg-gray-200 border border-transparent rounded-md text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition">ยกเลิก</button>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic">ยังไม่มีหน่วยงานสำหรับบริษัทนี้</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
