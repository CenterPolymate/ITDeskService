<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ตั้งค่าระบบและการแจ้งเตือน (System Settings)') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-md shadow-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium leading-5">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-md shadow-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <strong class="font-semibold text-sm block">เกิดข้อผิดพลาดในการเชื่อมต่อ</strong>
                        <span class="text-xs text-rose-700 block mt-0.5 whitespace-pre-line">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form id="settingsForm" method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">LINE OA Settings</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="line_channel_access_token" :value="__('Channel Access Token')" />
                                <x-text-input id="line_channel_access_token" class="block mt-1 w-full" type="text" name="line_channel_access_token" :value="old('line_channel_access_token', $settings['line_channel_access_token']->value ?? '')" />
                            </div>

                            <div>
                                <x-input-label for="line_channel_secret" :value="__('Channel Secret')" />
                                <x-text-input id="line_channel_secret" class="block mt-1 w-full" type="password" name="line_channel_secret" :value="old('line_channel_secret', $settings['line_channel_secret']->value ?? '')" />
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b pb-2 mt-8 mb-4 gap-2">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Email (SMTP) Settings</h3>
                                <p class="text-xs text-gray-500">การตั้งค่าเซิร์ฟเวอร์ส่งอีเมลสำหรับการแจ้งเตือนและการยืนยันตัวตน (Email Verification)</p>
                            </div>
                            <button type="button" 
                                    onclick="openTestEmailModal()" 
                                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white text-xs font-semibold rounded-md shadow-sm transition duration-150 ease-in-out cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span>ตรวจสอบการตั้งค่า (Test Connection)</span>
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="smtp_host" :value="__('SMTP Host')" />
                                <x-text-input id="smtp_host" class="block mt-1 w-full" type="text" name="smtp_host" :value="old('smtp_host', $settings['smtp_host']->value ?? 'smtp.mailtrap.io')" placeholder="เช่น mailpro-02.zth.netdesignhost.com" />
                            </div>

                            <div>
                                <x-input-label for="smtp_port" :value="__('SMTP Port')" />
                                <x-text-input id="smtp_port" class="block mt-1 w-full" type="text" name="smtp_port" :value="old('smtp_port', $settings['smtp_port']->value ?? '2525')" placeholder="เช่น 465 หรือ 587" />
                            </div>

                            <div>
                                <x-input-label for="smtp_username" :value="__('อีเมลผู้ส่ง (Sender Email / SMTP Username)')" />
                                <x-text-input id="smtp_username" class="block mt-1 w-full" type="text" name="smtp_username" :value="old('smtp_username', $settings['smtp_username']->value ?? $settings['smtp_from_address']->value ?? '')" placeholder="เช่น admin@polymate.co.th" />
                                <p class="mt-1 text-xs text-gray-500">ระบบจะใช้อีเมลนี้เป็นทั้งบัญชีล็อกอินส่งเมล (Username) และที่อยู่อีเมลผู้ส่ง (From Address) โดยอัตโนมัติ</p>
                            </div>

                            <div>
                                <x-input-label for="smtp_password" :value="__('รหัสผ่าน (SMTP Password)')" />
                                <x-text-input id="smtp_password" class="block mt-1 w-full" type="password" name="smtp_password" :value="old('smtp_password', $settings['smtp_password']->value ?? '')" />
                            </div>
                            
                            <div>
                                <x-input-label for="smtp_encryption" :value="__('Encryption (tls, ssl)')" />
                                <x-text-input id="smtp_encryption" class="block mt-1 w-full" type="text" name="smtp_encryption" :value="old('smtp_encryption', $settings['smtp_encryption']->value ?? 'tls')" placeholder="tls หรือ ssl (พอร์ต 465 มักใช้ ssl)" />
                            </div>
                            
                            <div class="rounded-lg bg-gray-50 border border-gray-200 p-3 text-xs text-gray-600 flex flex-col justify-center">
                                <span class="font-semibold text-gray-700 mb-1 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    คำแนะนำการตั้งค่าพอร์ตและการเข้ารหัส:
                                </span>
                                <ul class="list-disc list-inside space-y-0.5 text-gray-500 text-[11px]">
                                    <li><strong>พอร์ต 465:</strong> ใช้ Encryption แบบ <code class="text-indigo-600 font-mono">ssl</code> (แนะนำสำหรับ Mail Server โฮสติ้ง)</li>
                                    <li><strong>พอร์ต 587:</strong> ใช้ Encryption แบบ <code class="text-indigo-600 font-mono">tls</code></li>
                                </ul>
                            </div>
                        </div>

                        <!-- แถบปุ่มตรวจสอบการตั้งค่า SMTP ใต้ช่องกรอกข้อมูล -->
                        <div class="mt-4 p-4 bg-amber-50/80 border border-amber-200 rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
                            <div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm font-bold text-amber-900">ต้องการทดสอบการเชื่อมต่อเซิร์ฟเวอร์ส่งอีเมล?</span>
                                </div>
                                <p class="text-xs text-amber-700 mt-1">สามารถกดทดสอบได้ทันทีก่อนบันทึก โดยระบบจะส่งอีเมลทดสอบจริงไปยังอีเมลที่คุณระบุ</p>
                            </div>
                            <button type="button" 
                                    onclick="openTestEmailModal()" 
                                    class="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white text-xs font-bold rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span>ตรวจสอบการตั้งค่า (Test Connection)</span>
                            </button>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mt-8 mb-4">ระบบดูแลรักษากลาง (Maintenance)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="audit_log_retention_years" :value="__('เก็บประวัติการใช้งาน (Audit Logs) ย้อนหลัง (ปี)')" />
                                <select id="audit_log_retention_years" name="audit_log_retention_years" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="1" {{ (old('audit_log_retention_years', $settings['audit_log_retention_years']->value ?? '1') == '1') ? 'selected' : '' }}>1 ปี</option>
                                    <option value="2" {{ (old('audit_log_retention_years', $settings['audit_log_retention_years']->value ?? '1') == '2') ? 'selected' : '' }}>2 ปี</option>
                                    <option value="3" {{ (old('audit_log_retention_years', $settings['audit_log_retention_years']->value ?? '1') == '3') ? 'selected' : '' }}>3 ปี</option>
                                    <option value="5" {{ (old('audit_log_retention_years', $settings['audit_log_retention_years']->value ?? '1') == '5') ? 'selected' : '' }}>5 ปี</option>
                                    <option value="99" {{ (old('audit_log_retention_years', $settings['audit_log_retention_years']->value ?? '1') == '99') ? 'selected' : '' }}>เก็บถาวร (99 ปี)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">ระบบจะทำการลบประวัติที่เก่ากว่าจำนวนปีที่ตั้งค่าไว้อัตโนมัติทุกๆ วัน เพื่อลดภาระของฐานข้อมูล</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 gap-3">
                            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm cursor-pointer">
                                บันทึกการตั้งค่า
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal ตรวจสอบการตั้งค่า Email (SMTP) -->
    <div id="testEmailModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" onclick="closeTestEmailModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                <form id="testEmailForm" method="POST" action="{{ route('settings.test_email') }}" onsubmit="handleTestEmailSubmit(event)">
                    @csrf
                    <input type="hidden" name="smtp_host" id="test_smtp_host">
                    <input type="hidden" name="smtp_port" id="test_smtp_port">
                    <input type="hidden" name="smtp_username" id="test_smtp_username">
                    <input type="hidden" name="smtp_password" id="test_smtp_password">
                    <input type="hidden" name="smtp_encryption" id="test_smtp_encryption">
                    <input type="hidden" name="smtp_from_address" id="test_smtp_from_address">

                    <div class="bg-white p-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 flex items-center justify-center h-11 w-11 rounded-full bg-amber-100 text-amber-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="w-full">
                                <h3 class="text-base font-bold text-gray-900" id="modal-title">
                                    ทดสอบการเชื่อมต่อ Email (SMTP)
                                </h3>
                                <p class="mt-1 text-xs text-gray-500 leading-relaxed">
                                    ระบบจะนำค่า Host, Port, Username และ Password ที่กรอกอยู่ในหน้าจอส่งอีเมลทดสอบจริงทันที
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div>
                                <label for="modal_test_email" class="block text-xs font-semibold text-gray-700">ส่งอีเมลทดสอบไปยัง:</label>
                                <input type="email" id="modal_test_email" name="test_email" value="{{ Auth::user()->email }}" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="ระบุอีเมลปลายทาง">
                                <p class="mt-1 text-[11px] text-gray-400">ค่าเริ่มต้นคืออีเมลผู้ดูแลระบบปัจจุบัน</p>
                            </div>

                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-xs text-gray-600 space-y-1">
                                <div class="flex justify-between"><span class="text-gray-400">Host:</span> <span id="summary_host" class="font-mono text-gray-800 font-semibold truncate max-w-[200px]">-</span></div>
                                <div class="flex justify-between"><span class="text-gray-400">Port:</span> <span id="summary_port" class="font-mono text-gray-800 font-semibold">-</span></div>
                                <div class="flex justify-between"><span class="text-gray-400">Encryption:</span> <span id="summary_encryption" class="font-mono text-gray-800 font-semibold">-</span></div>
                                <div class="flex justify-between"><span class="text-gray-400">อีเมลผู้ส่ง:</span> <span id="summary_sender" class="font-mono text-gray-800 font-semibold truncate max-w-[200px]">-</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3.5 flex items-center justify-end gap-2 border-t border-gray-100">
                        <button type="button" onclick="closeTestEmailModal()" class="px-3.5 py-2 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-100 border border-gray-300 rounded-md shadow-xs transition cursor-pointer">
                            ยกเลิก
                        </button>
                        <button type="submit" id="btnSubmitTestEmail" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-xs font-semibold text-white rounded-md shadow-sm transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            <span id="btnSubmitText">ส่งอีเมลทดสอบ</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openTestEmailModal() {
            const host = document.getElementById('smtp_host')?.value || '';
            const port = document.getElementById('smtp_port')?.value || '';
            const senderEmail = document.getElementById('smtp_username')?.value || '';
            const password = document.getElementById('smtp_password')?.value || '';
            const encryption = document.getElementById('smtp_encryption')?.value || '';

            if (!host) {
                alert('กรุณากรอก SMTP Host ก่อนทดสอบ');
                return;
            }

            if (!senderEmail) {
                alert('กรุณากรอกอีเมลผู้ส่ง (Sender Email / SMTP Username) ก่อนทดสอบ');
                return;
            }

            // Sync hidden inputs in test modal
            document.getElementById('test_smtp_host').value = host;
            document.getElementById('test_smtp_port').value = port;
            document.getElementById('test_smtp_username').value = senderEmail;
            document.getElementById('test_smtp_password').value = password;
            document.getElementById('test_smtp_encryption').value = encryption;
            document.getElementById('test_smtp_from_address').value = senderEmail;

            // Update summary
            document.getElementById('summary_host').textContent = host || '-';
            document.getElementById('summary_port').textContent = port || '-';
            document.getElementById('summary_encryption').textContent = encryption || 'None';
            document.getElementById('summary_sender').textContent = senderEmail || '-';

            document.getElementById('testEmailModal').classList.remove('hidden');
        }

        function closeTestEmailModal() {
            document.getElementById('testEmailModal').classList.add('hidden');
        }

        function handleTestEmailSubmit(e) {
            const btn = document.getElementById('btnSubmitTestEmail');
            const txt = document.getElementById('btnSubmitText');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            txt.textContent = 'กำลังทดสอบการเชื่อมต่อ...';
        }
    </script>
</x-app-layout>
