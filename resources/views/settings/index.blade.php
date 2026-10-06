<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ตั้งค่าระบบและการแจ้งเตือน (System Settings)') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('settings.update') }}">
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

                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mt-8 mb-4">Email (SMTP) Settings</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="smtp_host" :value="__('SMTP Host')" />
                                <x-text-input id="smtp_host" class="block mt-1 w-full" type="text" name="smtp_host" :value="old('smtp_host', $settings['smtp_host']->value ?? 'smtp.mailtrap.io')" />
                            </div>

                            <div>
                                <x-input-label for="smtp_port" :value="__('SMTP Port')" />
                                <x-text-input id="smtp_port" class="block mt-1 w-full" type="text" name="smtp_port" :value="old('smtp_port', $settings['smtp_port']->value ?? '2525')" />
                            </div>

                            <div>
                                <x-input-label for="smtp_username" :value="__('SMTP Username')" />
                                <x-text-input id="smtp_username" class="block mt-1 w-full" type="text" name="smtp_username" :value="old('smtp_username', $settings['smtp_username']->value ?? '')" />
                            </div>

                            <div>
                                <x-input-label for="smtp_password" :value="__('SMTP Password')" />
                                <x-text-input id="smtp_password" class="block mt-1 w-full" type="password" name="smtp_password" :value="old('smtp_password', $settings['smtp_password']->value ?? '')" />
                            </div>
                            
                            <div>
                                <x-input-label for="smtp_encryption" :value="__('Encryption (tls, ssl)')" />
                                <x-text-input id="smtp_encryption" class="block mt-1 w-full" type="text" name="smtp_encryption" :value="old('smtp_encryption', $settings['smtp_encryption']->value ?? 'tls')" />
                            </div>
                            
                            <div>
                                <x-input-label for="smtp_from_address" :value="__('From Address')" />
                                <x-text-input id="smtp_from_address" class="block mt-1 w-full" type="text" name="smtp_from_address" :value="old('smtp_from_address', $settings['smtp_from_address']->value ?? 'hello@example.com')" />
                            </div>
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
                            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                                บันทึกการตั้งค่า
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
