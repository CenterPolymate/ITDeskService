<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('เปิดใบงานแจ้งซ่อมใหม่') }}
        </h2>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="bg-white p-6 sm:p-10 rounded-2xl shadow-sm border border-slate-200">
                @csrf

                <!-- Section 1: ข้อมูลผู้แจ้ง -->
                <div class="mb-8">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">ข้อมูลผู้แจ้ง</h3>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50 p-4 rounded-xl border border-slate-100">
                        <div>
                            <p class="font-bold text-slate-800">{{ Auth::user()->name }}</p>
                            <p class="text-sm text-slate-500">{{ Auth::user()->department ?: 'ไม่ระบุแผนก' }} • {{ Auth::user()->email }}</p>
                        </div>
                        <div class="w-full sm:w-1/2">
                            <label for="requester_phone" class="block text-sm font-medium text-slate-700 mb-1">
                                เบอร์ติดต่อ <span class="text-rose-500">*</span>
                            </label>
                            <input id="requester_phone" type="text" name="requester_phone" value="{{ old('requester_phone') }}" required pattern="^0[0-9]{1,2}-?[0-9]{3}-?[0-9]{4}$" placeholder="081-123-4567" 
                                class="block w-full py-2 px-3 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm sm:text-sm transition-colors">
                            <x-input-error :messages="$errors->get('requester_phone')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100 my-8">

                <!-- Section 2: ข้อมูลเครื่องจักร -->
                <div class="mb-8 space-y-5">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">อุปกรณ์ & สถานะ</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="actual_user_name" class="block text-sm font-medium text-slate-700 mb-1">แจ้งแทนผู้อื่น (ชื่อผู้ใช้งาน)</label>
                            <input id="actual_user_name" type="text" name="actual_user_name" value="{{ old('actual_user_name') }}" placeholder="ปล่อยว่างหากแจ้งให้ตนเอง" 
                                class="block w-full py-2 px-3 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm sm:text-sm transition-colors">
                            <x-input-error :messages="$errors->get('actual_user_name')" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">สถานะเครื่องจักร <span class="text-rose-500">*</span></label>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="is_machine_stopped" value="0" required class="text-indigo-600 focus:ring-indigo-500" {{ old('is_machine_stopped') === '0' ? 'checked' : '' }}>
                                    <span class="text-sm text-slate-700">ใช้งานได้ปกติ</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="is_machine_stopped" value="1" required class="text-rose-600 focus:ring-rose-500" {{ old('is_machine_stopped') === '1' ? 'checked' : '' }}>
                                    <span class="text-sm text-slate-700">เครื่องจักรหยุดทำงาน</span>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('is_machine_stopped')" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="machine_name" class="block text-sm font-medium text-slate-700 mb-1">ชื่ออุปกรณ์</label>
                            <input id="machine_name" type="text" name="machine_name" value="{{ old('machine_name') }}" placeholder="เช่น PC-01" 
                                class="block w-full py-2 px-3 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm sm:text-sm transition-colors">
                            <x-input-error :messages="$errors->get('machine_name')" class="mt-1" />
                        </div>
                        <div>
                            <label for="machine_code" class="block text-sm font-medium text-slate-700 mb-1">รหัสอุปกรณ์</label>
                            <input id="machine_code" type="text" name="machine_code" value="{{ old('machine_code') }}" placeholder="เช่น MCH-2026" 
                                class="block w-full py-2 px-3 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm sm:text-sm transition-colors">
                            <x-input-error :messages="$errors->get('machine_code')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100 my-8">

                <!-- Section 3: รายละเอียดปัญหา -->
                <div class="mb-8 space-y-5">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">รายละเอียดปัญหา</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="category" class="block text-sm font-medium text-slate-700 mb-1">หมวดหมู่</label>
                            <select id="category" name="category" class="block w-full py-2 px-3 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm sm:text-sm transition-colors">
                                <option value="" disabled selected>-- เลือกหมวดหมู่ --</option>
                                <option value="Hardware" {{ old('category') == 'Hardware' ? 'selected' : '' }}>Hardware</option>
                                <option value="Software" {{ old('category') == 'Software' ? 'selected' : '' }}>Software</option>
                                <option value="Network" {{ old('category') == 'Network' ? 'selected' : '' }}>Network</option>
                                <option value="Other" {{ old('category') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <x-input-error :messages="$errors->get('category')" class="mt-1" />
                        </div>

                        <div>
                            <label for="location" class="block text-sm font-medium text-slate-700 mb-1">
                                สถานที่ <span class="text-rose-500">*</span>
                            </label>
                            <input id="location" type="text" name="location" value="{{ old('location') }}" required placeholder="ระบุสถานที่" 
                                class="block w-full py-2 px-3 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm sm:text-sm transition-colors">
                            <x-input-error :messages="$errors->get('location')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <label for="title" class="flex justify-between items-end mb-1">
                            <span class="block text-sm font-medium text-slate-700">หัวข้อ <span class="text-rose-500">*</span></span>
                            <span class="text-xs text-slate-400"><span id="title-counter">0</span>/100</span>
                        </label>
                        <input id="title" type="text" name="title" value="{{ old('title') }}" maxlength="100" required placeholder="สรุปอาการสั้นๆ" oninput="document.getElementById('title-counter').innerText = this.value.length;" 
                            class="block w-full py-2 px-3 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm sm:text-sm transition-colors font-medium">
                        <x-input-error :messages="$errors->get('title')" class="mt-1" />
                    </div>

                    <div>
                        <label for="description" class="flex justify-between items-end mb-1">
                            <span class="block text-sm font-medium text-slate-700">รายละเอียด <span class="text-rose-500">*</span></span>
                        </label>
                        <textarea id="description" name="description" rows="4" maxlength="400" required placeholder="อธิบายปัญหาอย่างละเอียด (จำกัด 4 บรรทัด)" 
                            class="block w-full py-3 px-3 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm transition-colors resize-none sm:text-sm">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>
                </div>

                <hr class="border-slate-100 my-8">

                <!-- Section 4: แนบไฟล์ -->
                <div class="mb-10">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">รูปภาพแนบ (สูงสุด 2 รูป)</h3>
                    
                    <div id="drop-zone" class="relative flex flex-col items-center justify-center p-6 border-2 border-slate-200 border-dashed rounded-xl hover:bg-slate-50 transition-colors bg-slate-50/50 min-h-[120px]">
                        <input id="attachments" name="attachments[]" type="file" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/*" capture="environment" onchange="handleFileSelect(this)">
                        
                        <div class="text-center pointer-events-none flex flex-col items-center justify-center z-10 transition-opacity duration-300" id="upload-content">
                            <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <p class="text-sm font-medium text-slate-600">แตะเพื่อเลือกรูปภาพ</p>
                        </div>

                        <div id="image-preview-container" class="hidden w-full relative z-30 flex flex-col items-center justify-center mt-2">
                            <div id="preview-grid" class="flex flex-row flex-wrap gap-3 justify-center mb-3"></div>
                            <button type="button" onclick="removeImage(event)" class="text-xs text-rose-500 font-medium hover:underline pointer-events-auto">
                                ลบและเลือกใหม่
                            </button>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
                </div>

                <!-- Submit Area -->
                <div class="flex justify-end gap-3 pt-4">
                    <a href="{{ route('dashboard') }}" class="px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 transition-colors flex items-center">
                        ยกเลิก
                    </a>
                    <button type="submit" class="px-6 py-2 bg-slate-800 text-white text-sm font-bold rounded-lg shadow hover:bg-slate-700 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors">
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
                        imgWrapper.className = 'w-20 h-20 rounded-lg border border-slate-200 overflow-hidden shadow-sm';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-full h-full object-cover';
                        
                        imgWrapper.appendChild(img);
                        previewGrid.appendChild(imgWrapper);
                    }
                    reader.readAsDataURL(file);
                });

                // Switch UI
                uploadContent.classList.add('hidden');
                previewContainer.classList.remove('hidden');
            }
        }

        function removeImage(e) {
            e.preventDefault();
            const input = document.getElementById('attachments');
            input.value = ''; // Clear file input
            
            document.getElementById('preview-grid').innerHTML = ''; // Clear previews
            document.getElementById('image-preview-container').classList.add('hidden');
            document.getElementById('upload-content').classList.remove('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Title count initialization
            const titleInput = document.getElementById('title');
            if(titleInput) {
                document.getElementById('title-counter').innerText = titleInput.value.length;
            }
        });
    </script>
</x-app-layout>
