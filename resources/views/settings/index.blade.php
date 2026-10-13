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
                        <input type="hidden" name="smtp_verified_token" id="smtp_verified_token" value="{{ old('smtp_verified_token', $initialSmtpToken ?? '') }}">
                        
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

                        <div class="border-b pb-2 mt-8 mb-4">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h3 class="text-lg font-medium text-gray-900">Email (SMTP) Settings</h3>
                                <span id="smtp_status_badge" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold"></span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">การตั้งค่าเซิร์ฟเวอร์ส่งอีเมลสำหรับการแจ้งเตือนและการยืนยันตัวตน (Email Verification)</p>
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
                                <x-input-label for="smtp_encryption" :value="__('การเข้ารหัสความปลอดภัย (Encryption)')" />
                                @php
                                    $currentEncryption = strtolower(old('smtp_encryption', $settings['smtp_encryption']->value ?? 'ssl'));
                                @endphp
                                <select id="smtp_encryption" name="smtp_encryption" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                    <option value="ssl" {{ $currentEncryption === 'ssl' ? 'selected' : '' }}>SSL (แนะนำสำหรับพอร์ต 465)</option>
                                    <option value="tls" {{ $currentEncryption === 'tls' ? 'selected' : '' }}>TLS / STARTTLS (แนะนำสำหรับพอร์ต 587)</option>
                                    <option value="none" {{ $currentEncryption === 'none' ? 'selected' : '' }}>None (ไม่เข้ารหัส)</option>
                                </select>
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

                        <!-- แถบแสดงสถานะผลลัพธ์และปุ่มตรวจสอบการตั้งค่า SMTP ใต้ช่องกรอกข้อมูล -->
                        <div id="smtp_live_banner" class="mt-4 p-4 rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs border transition-all duration-200">
                            <!-- Populated dynamically via JS -->
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

                            <!-- กล่องแสดงผลลัพธ์การทดสอบใน Modal -->
                            <div id="modal_result_container" class="mt-4 hidden"></div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3.5 flex items-center justify-end gap-2 border-t border-gray-100">
                        <button type="button" onclick="closeTestEmailModal()" class="px-3.5 py-2 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-100 border border-gray-300 rounded-md shadow-xs transition cursor-pointer">
                            ปิดหน้าต่าง
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
        let isSmtpVerified = {{ ($savedVerified && !empty($initialSmtpToken)) ? 'true' : 'false' }};
        let lastTestResult = null; // { success: boolean, message: string }

        function updateSmtpUiStatus() {
            const hostInput = document.getElementById('smtp_host');
            const hostVal = hostInput ? hostInput.value.trim() : '';
            const isConfigured = hostVal && hostVal !== 'smtp.mailtrap.io';

            const badge = document.getElementById('smtp_status_badge');
            const banner = document.getElementById('smtp_live_banner');

            if (!badge || !banner) return;

            if (!isConfigured) {
                badge.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200';
                badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> ยังไม่ได้ตั้งค่า';

                banner.className = 'mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs';
                banner.innerHTML = `
                    <div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-sm font-bold text-gray-700">ยังไม่มีการตั้งค่า Mail Server</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">กรอกข้อมูล Host, Port, อีเมลผู้ส่ง และ Password เพื่อเริ่มใช้งานระบบส่งอีเมล</p>
                    </div>
                    <button type="button" onclick="openTestEmailModal()" class="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition cursor-pointer">
                        <span>ตรวจสอบการตั้งค่า (Test Connection)</span>
                    </button>
                `;
                return;
            }

            if (lastTestResult) {
                if (lastTestResult.success) {
                    badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300';
                    badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> ผ่านการตรวจสอบแล้ว (พร้อมใช้งาน)';

                    banner.className = 'mt-4 p-4 bg-emerald-50/90 border border-emerald-300 rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs';
                    banner.innerHTML = `
                        <div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="text-sm font-bold text-emerald-900">ผลการตรวจสอบ: ผ่าน (Connected Successfully)</span>
                            </div>
                            <p class="text-xs text-emerald-700 mt-1">${lastTestResult.message} — สามารถกดบันทึกการตั้งค่าระบบได้</p>
                        </div>
                        <button type="button" onclick="openTestEmailModal()" class="shrink-0 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span>ทดสอบซ้ำ</span>
                        </button>
                    `;
                } else {
                    badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-300';
                    badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span> ตรวจสอบไม่ผ่าน (ห้ามบันทึก)';

                    banner.className = 'mt-4 p-4 bg-rose-50 border border-rose-300 rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs';
                    banner.innerHTML = `
                        <div class="max-w-xl">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="text-sm font-bold text-rose-900">ผลการตรวจสอบ: ไม่ผ่าน (Connection Failed)</span>
                            </div>
                            <p class="text-xs text-rose-700 mt-1 break-words">${lastTestResult.message}</p>
                            <p class="text-[11px] font-semibold text-rose-800 mt-1">⚠️ ระบบจะไม่อนุญาตให้บันทึกการตั้งค่าจนกว่าจะแก้ไขและทดสอบผ่าน เพื่อป้องกันไม่ให้ระบบส่งอีเมลล้มเหลว</p>
                        </div>
                        <button type="button" onclick="openTestEmailModal()" class="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-sm transition cursor-pointer">
                            <span>แก้ไขและตรวจสอบใหม่</span>
                        </button>
                    `;
                }
            } else if (isSmtpVerified) {
                badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300';
                badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> ผ่านการตรวจสอบแล้ว';

                banner.className = 'mt-4 p-4 bg-emerald-50/80 border border-emerald-200 rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs';
                banner.innerHTML = `
                    <div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-sm font-bold text-emerald-900">การตั้งค่าอีเมลพร้อมใช้งาน</span>
                        </div>
                        <p class="text-xs text-emerald-700 mt-1">การตั้งค่าปัจจุบันได้รับการตรวจสอบแล้ว สามารถกดบันทึกการตั้งค่าระบบได้ตามปกติ</p>
                    </div>
                    <button type="button" onclick="openTestEmailModal()" class="shrink-0 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>ทดสอบการเชื่อมต่อ</span>
                    </button>
                `;
            } else {
                badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-300';
                badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> ยังไม่ผ่านการตรวจสอบ (ต้องทดสอบก่อนบันทึก)';

                banner.className = 'mt-4 p-4 bg-amber-50/90 border border-amber-300 rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs';
                banner.innerHTML = `
                    <div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span class="text-sm font-bold text-amber-900">ต้องตรวจสอบการตั้งค่าก่อนบันทึก</span>
                        </div>
                        <p class="text-xs text-amber-700 mt-1">กรุณากด 'ตรวจสอบการตั้งค่า (Test Connection)' ให้ผ่านก่อนบันทึก เพื่อป้องกันไม่ให้ระบบส่งอีเมลล้มเหลว</p>
                    </div>
                    <button type="button" onclick="openTestEmailModal()" class="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white text-xs font-bold rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span>ตรวจสอบการตั้งค่า (Test Connection)</span>
                    </button>
                `;
            }
        }

        // รีเซ็ตสถานะเมื่อมีการแก้ไขข้อมูลฟิลด์ SMTP
        const smtpInputs = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption'];
        smtpInputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                ['input', 'change'].forEach(evt => {
                    el.addEventListener(evt, () => {
                        isSmtpVerified = false;
                        lastTestResult = null;
                        const tokenEl = document.getElementById('smtp_verified_token');
                        if (tokenEl) tokenEl.value = '';
                        updateSmtpUiStatus();
                    });
                });
            }
        });

        // สลับค่า Encryption อัตโนมัติเมื่อพิมพ์หรือเปลี่ยนพอร์ต 465 หรือ 587
        const portInput = document.getElementById('smtp_port');
        const encSelect = document.getElementById('smtp_encryption');
        if (portInput && encSelect) {
            portInput.addEventListener('change', () => {
                const p = portInput.value.trim();
                if (p === '465' && encSelect.value !== 'ssl') {
                    encSelect.value = 'ssl';
                    encSelect.dispatchEvent(new Event('change'));
                } else if (p === '587' && encSelect.value !== 'tls') {
                    encSelect.value = 'tls';
                    encSelect.dispatchEvent(new Event('change'));
                }
            });
        }

        // ป้องกันการกดบันทึกการตั้งค่าถ้า SMTP ยังไม่ผ่านการตรวจสอบ
        const settingsForm = document.getElementById('settingsForm');
        if (settingsForm) {
            settingsForm.addEventListener('submit', function(e) {
                const hostVal = document.getElementById('smtp_host')?.value.trim();
                const isConfigured = hostVal && hostVal !== 'smtp.mailtrap.io';

                if (isConfigured && !isSmtpVerified) {
                    e.preventDefault();

                    let errorReason = 'การตั้งค่า Email (SMTP) ยังไม่ผ่านการตรวจสอบความถูกต้อง';
                    if (lastTestResult && !lastTestResult.success) {
                        errorReason = 'ผลการตรวจสอบการเชื่อมต่อ SMTP ล่าสุด "ไม่ผ่าน" (' + lastTestResult.message + ')';
                    }

                    alert('⛔ ไม่สามารถบันทึกการตั้งค่าได้!\n\n' + errorReason + '\n\nกรุณากดปุ่ม "ตรวจสอบการตั้งค่า (Test Connection)" ให้ผ่านก่อนบันทึก เพื่อป้องกันไม่ให้ระบบส่งอีเมลล้มเหลว');

                    const banner = document.getElementById('smtp_live_banner');
                    if (banner) {
                        banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        banner.classList.add('ring-4', 'ring-rose-400');
                        setTimeout(() => banner.classList.remove('ring-4', 'ring-rose-400'), 2500);
                    }
                    return false;
                }
            });
        }

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

            // Reset modal result box
            const resultBox = document.getElementById('modal_result_container');
            if (resultBox) {
                resultBox.className = 'mt-4 hidden';
                resultBox.innerHTML = '';
            }

            document.getElementById('testEmailModal').classList.remove('hidden');
        }

        function closeTestEmailModal() {
            document.getElementById('testEmailModal').classList.add('hidden');
        }

        async function handleTestEmailSubmit(e) {
            e.preventDefault();

            const btn = document.getElementById('btnSubmitTestEmail');
            const txt = document.getElementById('btnSubmitText');
            const resultBox = document.getElementById('modal_result_container');

            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            txt.textContent = 'กำลังทดสอบการเชื่อมต่อ...';

            resultBox.className = 'mt-4 p-3.5 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-xs flex items-center gap-2.5 shadow-xs';
            resultBox.innerHTML = `
                <svg class="animate-spin h-4 w-4 text-blue-600 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>กำลังเชื่อมต่อกับเซิร์ฟเวอร์ SMTP และส่งอีเมลทดสอบ... (อาจใช้เวลา 3-5 วินาที)</span>
            `;
            resultBox.classList.remove('hidden');

            const form = document.getElementById('testEmailForm');
            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    isSmtpVerified = true;
                    lastTestResult = { success: true, message: data.message };
                    const tokenInput = document.getElementById('smtp_verified_token');
                    if (tokenInput) tokenInput.value = data.verified_token || '';

                    resultBox.className = 'mt-4 p-4 bg-emerald-50 border border-emerald-300 rounded-lg text-emerald-800 text-xs flex items-start gap-3 shadow-xs';
                    resultBox.innerHTML = `
                        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <div class="font-bold text-sm text-emerald-900">ผลการทดสอบ: ผ่าน (Passed)</div>
                            <div class="mt-1 text-emerald-700 leading-relaxed">${data.message}</div>
                            <div class="mt-2 text-xs font-semibold text-emerald-900 bg-emerald-100 px-2.5 py-1 rounded border border-emerald-200 inline-block">
                                ✅ สามารถกด "บันทึกการตั้งค่า" ได้แล้ว
                            </div>
                        </div>
                    `;
                    txt.textContent = 'ทดสอบอีกครั้ง';
                } else {
                    isSmtpVerified = false;
                    lastTestResult = { success: false, message: data.message || 'การเชื่อมต่อ SMTP ล้มเหลว' };
                    const tokenInput = document.getElementById('smtp_verified_token');
                    if (tokenInput) tokenInput.value = '';

                    resultBox.className = 'mt-4 p-4 bg-rose-50 border border-rose-300 rounded-lg text-rose-800 text-xs flex items-start gap-3 shadow-xs';
                    resultBox.innerHTML = `
                        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <div class="font-bold text-sm text-rose-900">ผลการทดสอบ: ไม่ผ่าน (Failed)</div>
                            <div class="mt-1 text-rose-700 leading-relaxed break-words">${data.message || 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'}</div>
                            <div class="mt-2 text-xs font-semibold text-rose-900 bg-rose-100 px-2.5 py-1 rounded border border-rose-200">
                                ⚠️ ระบบจะไม่อนุญาตให้บันทึกจนกว่าจะแก้ไขและทดสอบผ่าน
                            </div>
                        </div>
                    `;
                    txt.textContent = 'ทดสอบใหม่';
                }
            } catch (err) {
                isSmtpVerified = false;
                lastTestResult = { success: false, message: 'เกิดข้อผิดพลาดในการส่งคำขอ: ' + err.message };
                const tokenInput = document.getElementById('smtp_verified_token');
                if (tokenInput) tokenInput.value = '';

                resultBox.className = 'mt-4 p-4 bg-rose-50 border border-rose-300 rounded-lg text-rose-800 text-xs flex items-start gap-3 shadow-xs';
                resultBox.innerHTML = `
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <div class="font-bold text-sm text-rose-900">ผลการทดสอบ: ไม่ผ่าน (Failed)</div>
                        <div class="mt-1 text-rose-700 leading-relaxed break-words">${err.message}</div>
                    </div>
                `;
                txt.textContent = 'ทดสอบใหม่';
            } finally {
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
                updateSmtpUiStatus();
            }
        }

        // อัปเดตสถานะเริ่มต้นเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', () => {
            updateSmtpUiStatus();
        });
    </script>
</x-app-layout>
