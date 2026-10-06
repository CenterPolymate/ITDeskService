<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl text-white shadow-md shadow-indigo-200">
                <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </div>
            <div>
                <h2 class="font-bold text-xl sm:text-2xl text-slate-800 leading-tight tracking-tight">
                    {{ __('เปิดใบงานแจ้งซ่อมใหม่') }}
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">กรอกข้อมูลปัญหาที่พบเพื่อให้ทีม IT ดำเนินการแก้ไข</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10 min-h-[calc(100vh-160px)]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-6 sm:space-y-8">
                @csrf

                <!-- Section 1: ข้อมูลผู้แจ้ง -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
                    <div class="bg-slate-50/50 px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                        <div class="p-1.5 bg-white rounded-lg border border-slate-200 shadow-sm text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-700">ข้อมูลผู้แจ้ง (Requester Info)</h3>
                    </div>
                    <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Profile Card for Name/Dept/Email -->
                        <div class="col-span-1 md:col-span-2 flex flex-col sm:flex-row items-center gap-4 p-4 bg-slate-50/80 rounded-xl border border-slate-200/80">
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-100 to-blue-100 border-2 border-white shadow-sm flex items-center justify-center text-indigo-700 font-bold text-2xl shrink-0">
                                {{ mb_substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div class="flex-1 text-center sm:text-left">
                                <h4 class="text-lg font-bold text-slate-800">{{ Auth::user()->name }}</h4>
                                <p class="text-sm text-slate-500 mt-0.5">{{ Auth::user()->department ?: 'ไม่ระบุแผนก' }} • {{ Auth::user()->email }}</p>
                            </div>
                        </div>

                        <div class="col-span-1 md:col-span-2">
                            <label for="requester_phone" class="block font-semibold text-sm text-slate-700 mb-1.5">
                                เบอร์โทรศัพท์ติดต่อกลับ <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                </div>
                                <input id="requester_phone" type="text" name="requester_phone" value="{{ old('requester_phone') }}" required pattern="^0[0-9]{1,2}-?[0-9]{3}-?[0-9]{4}$" title="กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง (เช่น 081-123-4567)" placeholder="เช่น 081-123-4567" 
                                    class="block w-full pl-11 py-2.5 bg-white border-slate-300 border-l-4 border-l-indigo-500 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all duration-200 sm:text-sm">
                            </div>
                            <x-input-error :messages="$errors->get('requester_phone')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Section 2: ข้อมูลเครื่องจักร -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
                    <div class="bg-slate-50/50 px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                        <div class="p-1.5 bg-white rounded-lg border border-slate-200 shadow-sm text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-700">ข้อมูลเครื่องจักร / ผู้ใช้งานจริง (Machine & User Details)</h3>
                    </div>
                    <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                        <div class="col-span-1 md:col-span-2">
                            <label for="actual_user_name" class="block font-semibold text-sm text-slate-700 mb-1.5">ชื่อผู้ใช้งานจริง (หากแจ้งแทนผู้อื่น)</label>
                            <input id="actual_user_name" type="text" name="actual_user_name" value="{{ old('actual_user_name') }}" placeholder="ปล่อยว่างหากแจ้งให้ตนเอง" 
                                class="block w-full py-2.5 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all duration-200 sm:text-sm">
                            <x-input-error :messages="$errors->get('actual_user_name')" class="mt-2" />
                        </div>
                        <div class="col-span-1">
                            <label for="machine_name" class="block font-semibold text-sm text-slate-700 mb-1.5">ชื่ออุปกรณ์ / เครื่องจักร</label>
                            <input id="machine_name" type="text" name="machine_name" value="{{ old('machine_name') }}" placeholder="เช่น PC-01" 
                                class="block w-full py-2.5 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all duration-200 sm:text-sm">
                            <x-input-error :messages="$errors->get('machine_name')" class="mt-2" />
                        </div>
                        <div class="col-span-1">
                            <label for="machine_code" class="block font-semibold text-sm text-slate-700 mb-1.5">รหัสอุปกรณ์</label>
                            <input id="machine_code" type="text" name="machine_code" value="{{ old('machine_code') }}" placeholder="เช่น MCH-2026" 
                                class="block w-full py-2.5 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all duration-200 sm:text-sm">
                            <x-input-error :messages="$errors->get('machine_code')" class="mt-2" />
                        </div>
                        
                        <div class="col-span-1 md:col-span-2 pt-2">
                            <label class="block font-semibold text-sm text-slate-700 mb-3">สถานะเครื่องจักร ณ ปัจจุบัน</label>
                            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                <label class="cursor-pointer relative group">
                                    <input type="radio" name="is_machine_stopped" value="1" class="peer sr-only" {{ old('is_machine_stopped') === '1' ? 'checked' : '' }}>
                                    <div class="flex items-center justify-center p-3 sm:p-4 bg-white border-2 border-slate-200 rounded-xl group-hover:bg-slate-50 peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700 transition-all duration-200 text-slate-600 font-bold text-sm sm:text-base">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <span class="truncate">เครื่องจักรหยุด</span>
                                    </div>
                                    <div class="absolute inset-0 border-2 border-rose-500 rounded-xl opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity duration-200"></div>
                                </label>
                                <label class="cursor-pointer relative group">
                                    <input type="radio" name="is_machine_stopped" value="0" class="peer sr-only" {{ old('is_machine_stopped') === '0' ? 'checked' : '' }}>
                                    <div class="flex items-center justify-center p-3 sm:p-4 bg-white border-2 border-slate-200 rounded-xl group-hover:bg-slate-50 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 transition-all duration-200 text-slate-600 font-bold text-sm sm:text-base">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span class="truncate">เครื่องจักรปกติ</span>
                                    </div>
                                    <div class="absolute inset-0 border-2 border-emerald-500 rounded-xl opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity duration-200"></div>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('is_machine_stopped')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Section 3: รายละเอียดปัญหา -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
                    <div class="bg-slate-50/50 px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                        <div class="p-1.5 bg-white rounded-lg border border-slate-200 shadow-sm text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-700">รายละเอียดปัญหา (Problem Details)</h3>
                    </div>
                    <div class="p-5 sm:p-6 space-y-5 sm:space-y-6">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                            <div>
                                <label for="category" class="block font-semibold text-sm text-slate-700 mb-1.5">หมวดหมู่ปัญหา</label>
                                <select id="category" name="category" class="block w-full py-2.5 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all duration-200 sm:text-sm">
                                    <option value="" disabled selected>-- เลือกหมวดหมู่ --</option>
                                    <option value="Hardware" {{ old('category') == 'Hardware' ? 'selected' : '' }}>อุปกรณ์ Hardware (คอม, ปริ้นเตอร์, เมาส์)</option>
                                    <option value="Software" {{ old('category') == 'Software' ? 'selected' : '' }}>โปรแกรม Software (Windows, Office, ERP)</option>
                                    <option value="Network" {{ old('category') == 'Network' ? 'selected' : '' }}>ระบบเครือข่าย Network (Internet, Wi-Fi, LAN)</option>
                                    <option value="Other" {{ old('category') == 'Other' ? 'selected' : '' }}>อื่นๆ</option>
                                </select>
                                <x-input-error :messages="$errors->get('category')" class="mt-2" />
                            </div>

                            <div>
                                <label for="location" class="block font-semibold text-sm text-slate-700 mb-1.5">
                                    สถานที่/จุดที่เกิดปัญหา <span class="text-rose-500">*</span>
                                </label>
                                <input id="location" type="text" name="location" value="{{ old('location') }}" required placeholder="เช่น อาคาร A ชั้น 2" 
                                    class="block w-full py-2.5 bg-white border-slate-300 border-l-4 border-l-indigo-500 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all duration-200 sm:text-sm">
                                <x-input-error :messages="$errors->get('location')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <label for="title" class="flex justify-between items-end mb-1.5">
                                <span class="font-semibold text-sm text-slate-700">หัวข้อปัญหา (Subject) <span class="text-rose-500">*</span></span>
                                <span class="text-xs text-slate-400 font-medium bg-slate-100 px-2 py-0.5 rounded-full"><span id="title-counter">0</span>/100</span>
                            </label>
                            <input id="title" type="text" name="title" value="{{ old('title') }}" maxlength="100" required placeholder="สรุปอาการสั้นๆ เช่น เปิดคอมไม่ติด, เน็ตหลุดบ่อย" oninput="document.getElementById('title-counter').innerText = this.value.length;" 
                                class="block w-full py-2.5 bg-white border-slate-300 border-l-4 border-l-indigo-500 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all duration-200 sm:text-sm font-medium">
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <label for="description" class="flex justify-between items-end mb-1.5">
                                <span class="font-semibold text-sm text-slate-700">รายละเอียดอาการ (Description) <span class="text-rose-500">*</span></span>
                                <span class="text-xs text-slate-400 font-medium bg-slate-100 px-2 py-0.5 rounded-full">
                                    บรรทัด: <span id="line-counter">1</span>/4 | 
                                    อักษร: <span id="desc-counter">0</span>/400
                                </span>
                            </label>
                            <textarea id="description" name="description" rows="4" maxlength="400" required placeholder="อธิบายปัญหาอย่างละเอียด (5W2H)&#10;What: เกิดอะไรขึ้น&#10;Where/When: ที่ไหน/เมื่อไหร่&#10;Why/How: ทำไม/อย่างไร" 
                                class="block w-full py-3 bg-white border-slate-300 border-l-4 border-l-indigo-500 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm transition-all duration-200 resize-none sm:text-sm">{{ old('description') }}</textarea>
                            <p class="text-xs text-slate-500 mt-2 font-medium">จำกัดสูงสุด 4 บรรทัด (เพื่อความพอดีในพื้นที่ใบงาน P-CAR)</p>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Section 4: แนบไฟล์ -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
                    <div class="bg-slate-50/50 px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                        <div class="p-1.5 bg-white rounded-lg border border-slate-200 shadow-sm text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-700">หลักฐาน / ไฟล์แนบ (Optional)</h3>
                    </div>
                    <div class="p-5 sm:p-6">
                        <div id="drop-zone" class="relative flex flex-col items-center justify-center p-6 sm:p-10 border-2 border-indigo-200/70 border-dashed rounded-2xl hover:bg-indigo-50/50 transition-all duration-300 group bg-slate-50/30 overflow-hidden">
                            <input id="attachments" name="attachments[]" type="file" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/*" capture="environment" onchange="handleFileSelect(this)">
                            
                            <div class="space-y-4 text-center pointer-events-none absolute inset-0 flex flex-col items-center justify-center z-10 transition-opacity duration-300" id="upload-content">
                                <div class="w-16 h-16 sm:w-20 sm:h-20 bg-white rounded-full flex items-center justify-center shadow-sm border border-slate-100 group-hover:scale-110 group-hover:shadow-md group-hover:border-indigo-100 transition-all duration-300">
                                    <svg class="w-8 h-8 sm:w-10 sm:h-10 text-indigo-500 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm sm:text-base font-bold text-indigo-700">แตะเพื่อถ่ายรูป / เลือกไฟล์</p>
                                    <p class="text-xs text-slate-500 mt-1.5 font-medium">PNG, JPG (สูงสุด 2 รูป / รูปละไม่เกิน 5MB)</p>
                                </div>
                            </div>

                            <div id="image-preview-container" class="hidden w-full relative z-30 flex flex-col items-center min-h-[140px] justify-center">
                                <div id="preview-grid" class="flex flex-row flex-wrap gap-4 justify-center mb-5 w-full"></div>
                                <button type="button" onclick="removeImage(event)" class="text-xs sm:text-sm text-rose-600 font-bold hover:text-rose-800 pointer-events-auto bg-rose-50 hover:bg-rose-100 px-5 py-2.5 rounded-full transition-colors flex items-center gap-2 shadow-sm border border-rose-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    ลบรูปและเลือกใหม่
                                </button>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('attachments')" class="mt-3" />
                    </div>
                </div>

                <!-- Submit Area -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-2">
                    <a href="{{ route('dashboard') }}" class="w-full sm:w-auto text-center px-6 py-3.5 sm:py-3 bg-white border-2 border-slate-200 rounded-xl font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 focus:ring-4 focus:ring-slate-100 transition-all duration-200">
                        ยกเลิก
                    </a>
                    <button type="submit" class="w-full sm:w-auto px-8 py-3.5 sm:py-3 bg-gradient-to-r from-indigo-600 to-blue-600 rounded-xl font-bold text-white shadow-lg shadow-indigo-200/50 hover:shadow-xl hover:shadow-indigo-300/50 hover:-translate-y-0.5 focus:ring-4 focus:ring-indigo-500/30 transition-all duration-200 flex items-center justify-center gap-2 text-lg sm:text-base">
                        <svg class="w-5 h-5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        ส่งใบแจ้งซ่อม
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function handleFileSelect(input) {
            const uploadContent = document.getElementById('upload-content');
            const previewContainer = document.getElementById('image-preview-container');
            const previewGrid = document.getElementById('preview-grid');

            if (input.files && input.files.length > 0) {
                if (input.files.length > 2) {
                    alert('สามารถแนบรูปภาพได้สูงสุด 2 รูปเท่านั้น');
                    removeImage(new Event('click'));
                    return;
                }

                let hasOversizedFile = false;
                Array.from(input.files).forEach(file => {
                    if (file.size > 5242880) hasOversizedFile = true;
                });

                if (hasOversizedFile) {
                    alert('มีไฟล์ขนาดใหญ่เกิน 5MB กรุณาเลือกไฟล์ใหม่');
                    removeImage(new Event('click'));
                    return;
                }
                
                // Clear previous previews
                previewGrid.innerHTML = '';
                
                // Show previews
                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imgWrapper = document.createElement('div');
                        imgWrapper.className = 'flex flex-col items-center bg-white p-1.5 rounded-xl border border-slate-200 shadow-sm w-[130px] sm:w-[150px]';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-full h-24 sm:h-28 object-cover rounded-lg mb-2 bg-slate-50';
                        
                        const name = document.createElement('span');
                        name.className = 'text-[10px] sm:text-xs text-slate-500 font-medium truncate w-full text-center px-1';
                        name.innerText = file.name;
                        
                        imgWrapper.appendChild(img);
                        imgWrapper.appendChild(name);
                        previewGrid.appendChild(imgWrapper);
                    }
                    reader.readAsDataURL(file);
                });

                uploadContent.classList.add('opacity-0');
                setTimeout(() => {
                    uploadContent.classList.add('hidden');
                    previewContainer.classList.remove('hidden');
                }, 200);
                
            } else {
                removeImage(new Event('click'));
            }
        }

        function removeImage(e) {
            if(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            const input = document.getElementById('attachments');
            if(input) input.value = '';
            
            document.getElementById('image-preview-container').classList.add('hidden');
            const uploadContent = document.getElementById('upload-content');
            uploadContent.classList.remove('hidden');
            setTimeout(() => {
                uploadContent.classList.remove('opacity-0');
            }, 50);
            
            document.getElementById('preview-grid').innerHTML = '';
        }

        const dropZone = document.getElementById('drop-zone');
        dropZone.addEventListener('dragover', (e) => {
            dropZone.classList.add('bg-indigo-50/80', 'border-indigo-400');
        });
        dropZone.addEventListener('dragleave', (e) => {
            dropZone.classList.remove('bg-indigo-50/80', 'border-indigo-400');
        });
        dropZone.addEventListener('drop', (e) => {
            dropZone.classList.remove('bg-indigo-50/80', 'border-indigo-400');
        });

        // Script for character and line limits
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            const titleCounter = document.getElementById('title-counter');
            if (titleInput) {
                titleCounter.innerText = titleInput.value.length;
                titleInput.addEventListener('input', function() {
                    titleCounter.innerText = this.value.length;
                });
            }

            const descInput = document.getElementById('description');
            const descCounter = document.getElementById('desc-counter');
            const lineCounter = document.getElementById('line-counter');

            if (descInput) {
                const updateDescInfo = () => {
                    descCounter.innerText = descInput.value.length;
                    let lines = descInput.value.split('\n');
                    lineCounter.innerText = lines.length;
                    
                    if (lines.length > 4) {
                        descInput.value = lines.slice(0, 4).join('\n');
                        lineCounter.innerText = 4;
                    }
                };
                
                descInput.addEventListener('input', updateDescInfo);
                descInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        if (this.value.split('\n').length >= 4) {
                            e.preventDefault();
                        }
                    }
                });
                updateDescInfo();
            }
        });
    </script>
</x-app-layout>
