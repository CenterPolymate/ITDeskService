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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="{ showMapModal: false, mapOriginalName: '', mapAction: 'create', mapNewName: '', mapTargetId: '' }">
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
                    <!-- Unmapped Departments -->
                    @if($unmappedDepartments->count() > 0)
                        <div class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-amber-800 mb-2 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                แผนกที่มีผู้ใช้งานพิมพ์ระบุเข้ามาเอง (ยังไม่ได้เพิ่มในระบบ)
                            </h4>
                            <p class="text-xs text-amber-700 mb-3">คลิกที่ปุ่มเพื่อเพิ่มแผนกเหล่านี้เข้าสู่ระบบการเลือก (Dropdown)</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($unmappedDepartments as $unmapped)
                                    <button type="button" @click="showMapModal = true; mapOriginalName = '{{ $unmapped }}'; mapNewName = '{{ $unmapped }}'; mapAction = 'create';" class="inline-flex items-center px-3 py-1.5 bg-white hover:bg-amber-100 border border-amber-300 rounded-md text-xs font-medium text-amber-900 transition-colors shadow-sm" title="คลิกเพื่อจัดการแผนก">
                                        {{ $unmapped }}
                                        <svg class="w-3.5 h-3.5 ml-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

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

                    <!-- Map Department Modal -->
                    <div x-show="showMapModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                            <div x-show="showMapModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" @click="showMapModal = false"></div>

                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                            <div x-show="showMapModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                                <form action="{{ route('companies.departments.map', $company->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="original_name" x-model="mapOriginalName">
                                    
                                    <div>
                                        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-amber-100 rounded-full">
                                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-5">
                                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">จัดการแผนก: <span x-text="mapOriginalName" class="text-indigo-600"></span></h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500">เลือกวิธีจัดการกับแผนกที่ผู้ใช้งานระบุเข้ามาเอง</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-5 space-y-4 text-left">
                                        <!-- Option 1: Create New -->
                                        <label class="flex p-3 border rounded-lg cursor-pointer hover:bg-gray-50" :class="{ 'border-indigo-500 bg-indigo-50': mapAction === 'create' }">
                                            <input type="radio" name="action" value="create" x-model="mapAction" class="w-4 h-4 mt-0.5 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                            <div class="ml-3 w-full">
                                                <span class="block text-sm font-medium text-gray-900">เพิ่มเป็นแผนกใหม่ (Add & Correct)</span>
                                                <span class="block text-sm text-gray-500">สร้างแผนกใหม่ และสามารถแก้ไขชื่อให้ถูกต้องได้</span>
                                                
                                                <div class="mt-3" x-show="mapAction === 'create'">
                                                    <label class="block text-xs text-gray-700 mb-1">ชื่อแผนกใหม่:</label>
                                                    <input type="text" name="new_name" x-model="mapNewName" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :required="mapAction === 'create'">
                                                </div>
                                            </div>
                                        </label>
                                        
                                        <!-- Option 2: Merge Existing -->
                                        <label class="flex p-3 border rounded-lg cursor-pointer hover:bg-gray-50" :class="{ 'border-indigo-500 bg-indigo-50': mapAction === 'merge' }">
                                            <input type="radio" name="action" value="merge" x-model="mapAction" class="w-4 h-4 mt-0.5 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                            <div class="ml-3 w-full">
                                                <span class="block text-sm font-medium text-gray-900">จับคู่กับแผนกเดิม (Merge)</span>
                                                <span class="block text-sm text-gray-500">ระบบจะทำการย้ายผู้ใช้ทั้งหมดในแผนกนี้ ไปยังแผนกที่เลือกไว้</span>
                                                
                                                <div class="mt-3" x-show="mapAction === 'merge'">
                                                    <label class="block text-xs text-gray-700 mb-1">เลือกแผนกเป้าหมาย:</label>
                                                    <select name="target_department_id" x-model="mapTargetId" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :required="mapAction === 'merge'">
                                                        <option value="">-- เลือกแผนก --</option>
                                                        @foreach($company->departments as $dept)
                                                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="mt-6 sm:mt-6 sm:flex sm:flex-row-reverse">
                                        <button type="submit" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                            บันทึก
                                        </button>
                                        <button type="button" @click="showMapModal = false" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                            ยกเลิก
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
