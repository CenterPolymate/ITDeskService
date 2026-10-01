<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('เปิดใบงานแจ้งซ่อมใหม่ (New Ticket)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-gray-900">
                    
                    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
                        @csrf



                        <!-- ข้อมูลผู้แจ้ง (Auto-filled but disabled to show user) -->
                        <div class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-4">ข้อมูลผู้แจ้ง (Requester Info)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="name" value="ชื่อ-นามสกุล" />
                                    <x-text-input id="name" class="block mt-1 w-full bg-gray-100 text-gray-500" type="text" value="{{ Auth::user()->name }}" disabled />
                                </div>
                                <div>
                                    <x-input-label for="department" value="แผนก/ฝ่าย" />
                                    <x-text-input id="department" class="block mt-1 w-full bg-gray-100 text-gray-500" type="text" value="{{ Auth::user()->department }}" disabled />
                                </div>
                                <div>
                                    <x-input-label for="email" value="อีเมล" />
                                    <x-text-input id="email" class="block mt-1 w-full bg-gray-100 text-gray-500" type="email" value="{{ Auth::user()->email }}" disabled />
                                </div>
                                <div>
                                    <label for="requester_phone" class="block font-medium text-sm text-red-600 mb-1">เบอร์โทรศัพท์ติดต่อกลับ <span class="text-red-500">*</span></label>
                                    <x-text-input id="requester_phone" class="block mt-1 w-full border-red-300 border-l-4 border-l-red-500 bg-red-50/30 focus:border-red-500 focus:ring-red-500" type="text" name="requester_phone" :value="old('requester_phone')" placeholder="เช่น 081-123-4567" required pattern="^0[0-9]{1,2}-?[0-9]{3}-?[0-9]{4}$" title="กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง (เช่น 081-123-4567 หรือ 0811234567)" />
                                    <x-input-error :messages="$errors->get('requester_phone')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Template ช่วยกรอกด่วน (Quick Issue Templates) -->
                        <div class="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-100" x-data="{
                            fillTemplate(title, category, desc) {
                                let t = document.getElementById('title'); t.value = title; t.dispatchEvent(new Event('input'));
                                document.getElementById('category').value = category;
                                let d = document.getElementById('description'); d.value = desc; d.dispatchEvent(new Event('input'));
                            }
                        }">
                            <h3 class="text-sm font-semibold text-blue-800 uppercase tracking-wider mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                อาการที่พบบ่อย (Quick Templates)
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="fillTemplate('เปิดคอมพิวเตอร์ไม่ติด / ไม่มีภาพหน้าจอ', 'Hardware', 'What: เปิดคอมพิวเตอร์ไม่ติด ไม่มีภาพขึ้นหน้าจอ\nWhere/When: \nWhy/How: ')" class="inline-flex items-center px-3 py-1.5 border border-blue-300 text-xs font-medium rounded-full text-blue-700 bg-white hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    💻 เปิดคอมไม่ติด
                                </button>
                                <button type="button" @click="fillTemplate('อินเทอร์เน็ตใช้งานไม่ได้ / หลุดบ่อย', 'Network', 'What: อินเทอร์เน็ตเชื่อมต่อไม่ได้ หรือหลุดบ่อย\nWhere/When: \nWhy/How: ')" class="inline-flex items-center px-3 py-1.5 border border-blue-300 text-xs font-medium rounded-full text-blue-700 bg-white hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    🌐 เน็ตหลุด/เข้าไม่ได้
                                </button>
                                <button type="button" @click="fillTemplate('เครื่องพิมพ์ปริ้นงานไม่ออก / กระดาษติด', 'Hardware', 'What: ปริ้นเตอร์ปริ้นไม่ออก หรือกระดาษติด\nWhere/When: \nWhy/How: ')" class="inline-flex items-center px-3 py-1.5 border border-blue-300 text-xs font-medium rounded-full text-blue-700 bg-white hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    🖨️ ปริ้นงานไม่ออก
                                </button>
                                <button type="button" @click="fillTemplate('ลืมรหัสผ่าน / เข้าใช้งานระบบไม่ได้', 'Software', 'What: ลืมรหัสผ่านเข้าใช้งานระบบ \nWhere/When: \nWhy/How: ')" class="inline-flex items-center px-3 py-1.5 border border-blue-300 text-xs font-medium rounded-full text-blue-700 bg-white hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    🔑 ลืมรหัสผ่าน
                                </button>
                            </div>
                        </div>

                        <!-- ข้อมูลปัญหา -->
                        <div class="mb-8">
                            <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-4">รายละเอียดปัญหา (Problem Details)</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="col-span-2 md:col-span-1">
                                    <x-input-label for="category" value="หมวดหมู่ปัญหา" />
                                    <select id="category" name="category" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="" disabled selected>-- เลือกหมวดหมู่ --</option>
                                        <option value="Hardware">อุปกรณ์ Hardware (คอม, ปริ้นเตอร์, เมาส์)</option>
                                        <option value="Software">โปรแกรม Software (Windows, Office, ERP)</option>
                                        <option value="Network">ระบบเครือข่าย Network (Internet, Wi-Fi, LAN)</option>
                                        <option value="Other">อื่นๆ</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                                </div>

                                <div class="col-span-2 md:col-span-1">
                                    <label for="location" class="block font-medium text-sm text-red-600 mb-1">สถานที่/จุดที่เกิดปัญหา <span class="text-red-500">*</span></label>
                                    <x-text-input id="location" class="block mt-1 w-full border-red-300 border-l-4 border-l-red-500 bg-red-50/30 focus:border-red-500 focus:ring-red-500" type="text" name="location" :value="old('location')" required placeholder="เช่น อาคาร A ชั้น 2" />
                                    <x-input-error :messages="$errors->get('location')" class="mt-2" />
                                </div>
                            </div>

                            <div class="mb-6">
                                <label for="title" class="block font-medium text-sm text-red-600 mb-1 flex justify-between">
                                    <span>หัวข้อปัญหา (Subject) <span class="text-red-500">*</span></span>
                                    <span class="text-xs text-gray-500"><span id="title-counter">0</span>/100</span>
                                </label>
                                <x-text-input id="title" class="block mt-1 w-full border-red-300 border-l-4 border-l-red-500 bg-red-50/30 focus:border-red-500 focus:ring-red-500" type="text" name="title" :value="old('title')" maxlength="100" required placeholder="สรุปอาการสั้นๆ เช่น เปิดคอมไม่ติด, เน็ตหลุดบ่อย" oninput="document.getElementById('title-counter').innerText = this.value.length;" />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div class="mb-6">
                                <label for="description" class="block font-medium text-sm text-red-600 mb-1 flex justify-between items-end">
                                    <span>รายละเอียดอาการ (Description) <span class="text-red-500">*</span></span>
                                    <span class="text-xs text-gray-500">
                                        บรรทัด: <span id="line-counter">1</span>/4 | 
                                        ตัวอักษร: <span id="desc-counter">0</span>/400
                                    </span>
                                </label>
                                <p class="text-xs text-red-500 mt-1 mb-2 font-medium">กรุณาใส่อาการของปัญหาให้ครบ (5W2H) - จำกัดสูงสุด 4 บรรทัด (เพื่อความพอดีในใบงาน)</p>
                                <textarea id="description" name="description" rows="4" maxlength="400" class="block w-full border-red-300 border-l-4 border-l-red-500 bg-red-50/30 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm resize-none" required placeholder="อธิบายปัญหาอย่างละเอียด หรือข้อความแจ้งเตือนที่ขึ้นบนหน้าจอ...">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <!-- แนบไฟล์รูปภาพ -->
                            <div class="mb-6">
                                <x-input-label for="attachment" value="แนบไฟล์รูปภาพ (Optional)" />
                                <div id="drop-zone" class="mt-1 relative flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition-colors min-h-[200px]">
                                    <input id="attachments" name="attachments[]" type="file" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/*" capture="environment" onchange="handleFileSelect(this)">
                                    <div class="space-y-1 text-center pointer-events-none absolute inset-0 flex flex-col items-center justify-center z-10" id="upload-content">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <span class="relative bg-transparent rounded-md font-medium text-indigo-600">
                                                <span id="file-upload-text">อัปโหลดไฟล์รูปภาพ (ได้สูงสุด 2 รูป)</span>
                                            </span>
                                            <p class="pl-1" id="drag-text">หรือลากไฟล์มาวางที่นี่</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG ขนาดไม่เกิน 5MB</p>
                                    </div>
                                    <div id="image-preview-container" class="hidden w-full relative z-30 flex flex-col items-center">
                                        <div id="preview-grid" class="flex flex-wrap gap-4 justify-center mb-2 w-full"></div>
                                        <button type="button" onclick="removeImage(event)" class="mt-2 text-xs text-red-500 font-medium hover:text-red-700 pointer-events-auto bg-white px-3 py-1.5 rounded shadow-sm border border-red-200">ลบรูปภาพทั้งหมด / ถ่ายใหม่</button>
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
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
                                        
                                        // Clear previous previews
                                        previewGrid.innerHTML = '';
                                        
                                        // Show previews
                                        Array.from(input.files).forEach(file => {
                                            const reader = new FileReader();
                                            reader.onload = function(e) {
                                                const imgWrapper = document.createElement('div');
                                                imgWrapper.className = 'flex flex-col items-center';
                                                
                                                const img = document.createElement('img');
                                                img.src = e.target.result;
                                                img.className = 'h-32 object-contain rounded-md shadow-sm border border-gray-200 mb-1 bg-white';
                                                
                                                const name = document.createElement('span');
                                                name.className = 'text-xs text-gray-500 truncate w-32 text-center';
                                                name.innerText = file.name;
                                                
                                                imgWrapper.appendChild(img);
                                                imgWrapper.appendChild(name);
                                                previewGrid.appendChild(imgWrapper);
                                            }
                                            reader.readAsDataURL(file);
                                        });

                                        uploadContent.classList.add('hidden');
                                        previewContainer.classList.remove('hidden');
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
                                    
                                    document.getElementById('upload-content').classList.remove('hidden');
                                    document.getElementById('image-preview-container').classList.add('hidden');
                                    document.getElementById('preview-grid').innerHTML = '';
                                }

                                const dropZone = document.getElementById('drop-zone');
                                dropZone.addEventListener('dragover', (e) => {
                                    dropZone.classList.add('bg-gray-100', 'border-indigo-500');
                                });
                                dropZone.addEventListener('dragleave', (e) => {
                                    dropZone.classList.remove('bg-gray-100', 'border-indigo-500');
                                });
                                dropZone.addEventListener('drop', (e) => {
                                    dropZone.classList.remove('bg-gray-100', 'border-indigo-500');
                                });

                                // Script for character and line limits
                                document.addEventListener('DOMContentLoaded', function() {
                                    const titleInput = document.getElementById('title');
                                    const titleCounter = document.getElementById('title-counter');
                                    if (titleInput) {
                                        titleCounter.innerText = titleInput.value.length;
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
                        </div>

                        <div class="flex items-center justify-end mt-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 mr-3">
                                ยกเลิก
                            </a>
                            <x-primary-button>
                                {{ __('ส่งใบแจ้งซ่อม (Submit)') }}
                            </x-primary-button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
