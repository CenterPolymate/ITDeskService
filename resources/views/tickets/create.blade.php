<x-app-layout>
    <!-- Playful Background -->
    <div class="fixed inset-0 z-[-1] bg-slate-50 overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-indigo-300/20 blur-[100px] animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-purple-300/20 blur-[100px] animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-3.5 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 rounded-2xl text-white shadow-lg shadow-purple-200 hover:scale-105 transition-transform duration-300 cursor-default">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <div>
                <h2 class="font-black text-2xl sm:text-3xl bg-clip-text text-transparent bg-gradient-to-r from-slate-800 to-indigo-800 tracking-tight">
                    {{ __('แจ้งปัญหาใหม่') }}
                </h2>
                <p class="text-sm sm:text-base text-slate-500 mt-1 font-medium">บอกเล่าปัญหาที่คุณเจอ ให้เราช่วยจัดการให้สิ 🚀</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12 min-h-[calc(100vh-160px)]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-8">
                @csrf

                <!-- Section 1: User Info (Floating Glass Card) -->
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/80 group hover:bg-white/90 transition-colors duration-500">
                    <div class="flex flex-col md:flex-row gap-6 md:items-center">
                        <div class="flex items-center gap-5 flex-1">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-100 to-indigo-100 border border-white shadow-inner flex items-center justify-center text-indigo-600 font-black text-2xl group-hover:rotate-3 transition-transform duration-300">
                                {{ mb_substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1">ข้อมูลของคุณ</p>
                                <h4 class="text-xl font-bold text-slate-800">{{ Auth::user()->name }}</h4>
                                <p class="text-sm text-slate-500">{{ Auth::user()->department ?: 'ไม่ระบุแผนก' }} • {{ Auth::user()->email }}</p>
                            </div>
                        </div>
                        <div class="w-full md:w-64">
                            <label for="requester_phone" class="block font-bold text-sm text-slate-700 mb-2">
                                เบอร์โทรติดต่อกลับ <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-lg">📱</span>
                                </div>
                                <input id="requester_phone" type="text" name="requester_phone" value="{{ old('requester_phone') }}" required pattern="^0[0-9]{1,2}-?[0-9]{3}-?[0-9]{4}$" placeholder="081-123-4567" 
                                    class="block w-full pl-12 py-3 bg-white/50 border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 rounded-2xl shadow-sm transition-all duration-300 font-medium">
                            </div>
                            <x-input-error :messages="$errors->get('requester_phone')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Machine Details (Playful Grid) -->
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/80">
                    <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <span class="p-2 bg-purple-100 text-purple-600 rounded-xl">💻</span> ข้อมูลอุปกรณ์
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="actual_user_name" class="block font-bold text-sm text-slate-700 mb-2">แจ้งแทนผู้อื่น (ระบุชื่อผู้ใช้จริง)</label>
                            <input id="actual_user_name" type="text" name="actual_user_name" value="{{ old('actual_user_name') }}" placeholder="ปล่อยว่างหากแจ้งให้ตนเอง" 
                                class="block w-full py-3 px-4 bg-white/50 border-slate-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 rounded-2xl shadow-sm transition-all duration-300 font-medium">
                            <x-input-error :messages="$errors->get('actual_user_name')" class="mt-2" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="machine_name" class="block font-bold text-sm text-slate-700 mb-2">ชื่ออุปกรณ์</label>
                                <input id="machine_name" type="text" name="machine_name" value="{{ old('machine_name') }}" placeholder="เช่น PC-01" 
                                    class="block w-full py-3 px-4 bg-white/50 border-slate-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 rounded-2xl shadow-sm transition-all duration-300 font-medium">
                            </div>
                            <div>
                                <label for="machine_code" class="block font-bold text-sm text-slate-700 mb-2">รหัสอุปกรณ์</label>
                                <input id="machine_code" type="text" name="machine_code" value="{{ old('machine_code') }}" placeholder="เช่น MCH-01" 
                                    class="block w-full py-3 px-4 bg-white/50 border-slate-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 rounded-2xl shadow-sm transition-all duration-300 font-medium">
                            </div>
                        </div>
                    </div>

                    <!-- Machine Status Interactive Cards -->
                    <div>
                        <label class="block font-bold text-sm text-slate-700 mb-3">สถานะเครื่องจักร ณ ปัจจุบัน <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="cursor-pointer relative group h-full block">
                                <input type="radio" name="is_machine_stopped" value="0" required class="peer sr-only" {{ old('is_machine_stopped') === '0' ? 'checked' : '' }}>
                                <div class="h-full flex flex-col items-center justify-center p-4 sm:p-6 bg-white border-2 border-slate-100 rounded-2xl group-hover:border-emerald-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:shadow-[0_0_20px_rgba(16,185,129,0.15)] transition-all duration-300 transform peer-checked:-translate-y-1">
                                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-500 text-2xl mb-3 group-hover:scale-110 transition-transform peer-checked:bg-emerald-500 peer-checked:text-white">
                                        ✨
                                    </div>
                                    <span class="font-bold text-slate-700 peer-checked:text-emerald-700 text-center">ใช้งานได้ปกติ</span>
                                </div>
                            </label>
                            
                            <label class="cursor-pointer relative group h-full block">
                                <input type="radio" name="is_machine_stopped" value="1" required class="peer sr-only" {{ old('is_machine_stopped') === '1' ? 'checked' : '' }}>
                                <div class="h-full flex flex-col items-center justify-center p-4 sm:p-6 bg-white border-2 border-slate-100 rounded-2xl group-hover:border-rose-200 peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:shadow-[0_0_20px_rgba(244,63,94,0.15)] transition-all duration-300 transform peer-checked:-translate-y-1">
                                    <div class="w-12 h-12 bg-rose-100 rounded-full flex items-center justify-center text-rose-500 text-2xl mb-3 group-hover:scale-110 transition-transform peer-checked:bg-rose-500 peer-checked:text-white">
                                        🚨
                                    </div>
                                    <span class="font-bold text-slate-700 peer-checked:text-rose-700 text-center">เครื่องจักรหยุดทำงาน</span>
                                </div>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('is_machine_stopped')" class="mt-2 text-center" />
                    </div>
                </div>

                <!-- Section 3: Problem Details -->
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/80">
                    <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <span class="p-2 bg-pink-100 text-pink-600 rounded-xl">📝</span> รายละเอียดปัญหา
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="category" class="block font-bold text-sm text-slate-700 mb-2">หมวดหมู่</label>
                            <select id="category" name="category" class="block w-full py-3 px-4 bg-white/50 border-slate-200 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 rounded-2xl shadow-sm transition-all duration-300 font-medium">
                                <option value="" disabled selected>-- เลือกประเภทปัญหา --</option>
                                <option value="Hardware" {{ old('category') == 'Hardware' ? 'selected' : '' }}>🖥️ อุปกรณ์ Hardware</option>
                                <option value="Software" {{ old('category') == 'Software' ? 'selected' : '' }}>💿 โปรแกรม Software</option>
                                <option value="Network" {{ old('category') == 'Network' ? 'selected' : '' }}>🌐 ระบบ Network</option>
                                <option value="Other" {{ old('category') == 'Other' ? 'selected' : '' }}>📦 อื่นๆ</option>
                            </select>
                            <x-input-error :messages="$errors->get('category')" class="mt-2" />
                        </div>
                        <div>
                            <label for="location" class="block font-bold text-sm text-slate-700 mb-2">
                                สถานที่ <span class="text-rose-500">*</span>
                            </label>
                            <input id="location" type="text" name="location" value="{{ old('location') }}" required placeholder="ระบุอาคาร, ชั้น, หรือโซน" 
                                class="block w-full py-3 px-4 bg-white/50 border-slate-200 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 rounded-2xl shadow-sm transition-all duration-300 font-medium">
                            <x-input-error :messages="$errors->get('location')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="title" class="flex justify-between items-end mb-2">
                            <span class="font-bold text-sm text-slate-700">หัวข้อปัญหา <span class="text-rose-500">*</span></span>
                            <span class="text-xs font-bold text-slate-400 bg-white px-3 py-1 rounded-full shadow-sm"><span id="title-counter">0</span>/100</span>
                        </label>
                        <input id="title" type="text" name="title" value="{{ old('title') }}" maxlength="100" required placeholder="สรุปสั้นๆ ให้เรารู้ว่าเกิดอะไรขึ้น เช่น เปิดคอมไม่ติดเลย" oninput="document.getElementById('title-counter').innerText = this.value.length;" 
                            class="block w-full py-3 px-4 bg-white border-slate-200 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 rounded-2xl shadow-sm transition-all duration-300 font-bold text-lg text-slate-800 placeholder-slate-300">
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <label for="description" class="block font-bold text-sm text-slate-700 mb-2">
                            รายละเอียดเพิ่มเติม <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="description" name="description" rows="4" maxlength="400" required placeholder="เล่าให้เราฟังหน่อยว่าเกิดอะไรขึ้นก่อนหน้านี้..." 
                            class="block w-full py-4 px-4 bg-white/50 border-slate-200 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/20 rounded-2xl shadow-sm transition-all duration-300 resize-none font-medium leading-relaxed">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>

                <!-- Section 4: File Upload -->
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/80">
                    <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <span class="p-2 bg-blue-100 text-blue-600 rounded-xl">📸</span> รูปภาพประกอบ
                    </h3>
                    
                    <div id="drop-zone" class="relative group cursor-pointer">
                        <input id="attachments" name="attachments[]" type="file" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/*" capture="environment" onchange="handleFileSelect(this)">
                        
                        <div class="flex flex-col items-center justify-center p-8 sm:p-12 border-2 border-dashed border-blue-200 bg-blue-50/50 rounded-3xl group-hover:bg-blue-50 group-hover:border-blue-400 transition-all duration-500">
                            <div id="upload-content" class="flex flex-col items-center text-center transition-all duration-300 group-hover:-translate-y-2">
                                <div class="w-20 h-20 bg-white shadow-xl shadow-blue-100 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-500">
                                    <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                </div>
                                <h4 class="text-lg font-bold text-blue-900 mb-1">แตะเพื่อถ่ายรูปหรืออัปโหลด</h4>
                                <p class="text-sm font-medium text-blue-600/70">รองรับ PNG, JPG (สูงสุด 2 รูป)</p>
                            </div>

                            <div id="image-preview-container" class="hidden w-full relative z-30 mt-4 flex flex-col items-center">
                                <div id="preview-grid" class="flex flex-row flex-wrap gap-4 justify-center mb-6"></div>
                                <button type="button" onclick="removeImage(event)" class="inline-flex items-center gap-2 px-6 py-2.5 bg-white text-rose-500 font-bold rounded-full shadow-sm hover:shadow-md hover:bg-rose-50 border border-rose-100 transition-all pointer-events-auto">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    ลบและเลือกใหม่
                                </button>
                            </div>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('attachments')" class="mt-3 text-center" />
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-end gap-4 pt-4 pb-10">
                    <a href="{{ route('dashboard') }}" class="w-full sm:w-auto px-8 py-4 bg-white text-slate-600 font-bold rounded-2xl shadow-sm hover:shadow-md hover:bg-slate-50 transition-all text-center">
                        ยกเลิก
                    </a>
                    <button type="submit" class="w-full sm:w-auto px-10 py-4 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white font-black rounded-2xl shadow-lg shadow-purple-200 hover:shadow-purple-300 hover:-translate-y-1 transition-all duration-300 text-lg flex items-center justify-center gap-3">
                        ส่งใบแจ้งซ่อม <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                
                previewGrid.innerHTML = '';
                
                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imgWrapper = document.createElement('div');
                        imgWrapper.className = 'w-24 h-24 sm:w-32 sm:h-32 rounded-2xl border-4 border-white shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-300 rotate-1 hover:rotate-0';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-full h-full object-cover';
                        
                        imgWrapper.appendChild(img);
                        previewGrid.appendChild(imgWrapper);
                    }
                    reader.readAsDataURL(file);
                });

                uploadContent.classList.add('hidden');
                previewContainer.classList.remove('hidden');
            }
        }

        function removeImage(e) {
            e.preventDefault();
            const input = document.getElementById('attachments');
            input.value = ''; 
            
            document.getElementById('preview-grid').innerHTML = ''; 
            document.getElementById('image-preview-container').classList.add('hidden');
            document.getElementById('upload-content').classList.remove('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            if(titleInput) {
                document.getElementById('title-counter').innerText = titleInput.value.length;
            }
        });
    </script>
</x-app-layout>
