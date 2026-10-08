@php /** @var \Illuminate\Database\Eloquent\Collection|\App\Models\Company[] $companies */ @endphp
<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="space-y-5" x-data="registerForm()">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="ระบุชื่อ-นามสกุลของคุณ" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200">
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-sm text-red-600" />
        </div>

        <!-- Company -->
        <div>
            <label for="company" class="block text-sm font-medium text-gray-700 mb-1">บริษัท (Company) <span class="text-red-500">*</span></label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <select id="company" name="company" required x-model="selectedCompany" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200">
                    <option value="" disabled selected>เลือกบริษัทของคุณ</option>
                    <template x-for="comp in companies" :key="comp.id">
                        <option :value="comp.name" x-text="comp.name"></option>
                    </template>
                </select>
            </div>
            <x-input-error :messages="$errors->get('company')" class="mt-2 text-sm text-red-600" />
        </div>

        <!-- Department -->
        <div>
            <label for="department" class="block text-sm font-medium text-gray-700 mb-1">แผนก (Department) <span class="text-red-500">*</span></label>
            <!-- Dropdown สำหรับเมื่อมีแผนก -->
            <div class="relative" x-show="departments.length > 0 && selectedDepartment !== 'other'" style="display: none;">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <select id="department_select" x-bind:name="(departments.length > 0 && selectedDepartment !== 'other') ? 'department' : ''" x-model="selectedDepartment" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200" :disabled="!selectedCompany" x-bind:required="departments.length > 0 && selectedDepartment !== 'other'">
                    <option value="">กรุณาเลือกแผนกที่ท่านสังกัด</option>
                    <template x-for="dept in departments" :key="dept.id">
                        <option :value="dept.name" x-text="dept.name"></option>
                    </template>
                    <option value="other">อื่นๆ (พิมพ์ระบุเอง)</option>
                </select>
            </div>

            <!-- Input พิมพ์เอง สำหรับเมื่อเลือกอื่นๆ หรือไม่มีแผนก -->
            <div class="relative" x-show="departments.length === 0 || selectedDepartment === 'other'" style="display: none;" :class="{ 'mt-2': departments.length > 0 && selectedDepartment === 'other' }">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <input id="department" type="text" x-bind:name="(departments.length === 0 || selectedDepartment === 'other') ? 'department' : ''" x-model="customDepartment" placeholder="กรุณาระบุแผนกที่ท่านสังกัด" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200" :disabled="!selectedCompany" x-bind:required="departments.length === 0 || selectedDepartment === 'other'">
                <button type="button" x-show="departments.length > 0 && selectedDepartment === 'other'" @click="selectedDepartment = ''" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">กลับไปเลือก</button>
            </div>
            
            <p x-show="selectedCompany && departments.length === 0" class="mt-1 text-xs text-gray-500">บริษัทนี้ยังไม่มีการตั้งค่าแผนก กรุณาพิมพ์ระบุเอง</p>
            <p x-show="!selectedCompany" class="mt-1 text-xs text-orange-500">กรุณาเลือกบริษัทก่อน</p>
            <x-input-error :messages="$errors->get('department')" class="mt-2 text-sm text-red-600" />
        </div>

        <!-- Phone -->
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์ (Phone)</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="ระบุเบอร์โทรศัพท์ของคุณ (ไม่บังคับ)" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200">
            </div>
            <x-input-error :messages="$errors->get('phone')" class="mt-2 text-sm text-red-600" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมลบริษัท (Company Email) <span class="text-red-500">*</span></label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="name@company.com" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-600" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน (Password) <span class="text-red-500">*</span></label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-600" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">ยืนยันรหัสผ่าน (Confirm Password) <span class="text-red-500">*</span></label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-white/50 backdrop-blur-sm transition duration-200">
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm text-red-600" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-indigo-600 hover:text-indigo-500 font-medium transition duration-150 ease-in-out" href="{{ route('login') }}">
                มีบัญชีอยู่แล้ว? เข้าสู่ระบบ
            </a>

            <button type="submit" class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-md text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transform transition hover:-translate-y-0.5">
                สมัครสมาชิก
            </button>
        </div>
    </form>

</x-guest-layout>

<script>
    function registerForm() {
        return {
            companies: @json($companies),
            selectedCompany: '{{ old('company', '') }}',
            selectedDepartment: '',
            customDepartment: '{{ old('department', '') }}',
            
            get departments() {
                if (!this.selectedCompany) return [];
                const company = this.companies.find(c => c.name === this.selectedCompany);
                return company && company.departments ? company.departments : [];
            },
            
            init() {
                // If there's old department data, we need to check if it matches a predefined one
                if (this.customDepartment) {
                    const depts = this.departments;
                    const match = depts.find(d => d.name === this.customDepartment);
                    if (match) {
                        this.selectedDepartment = match.name;
                        this.customDepartment = '';
                    } else if (depts.length > 0) {
                        this.selectedDepartment = 'other';
                    }
                }

                let initialDepartment = this.selectedDepartment;
                this.$nextTick(() => {
                    if (initialDepartment) {
                        this.selectedDepartment = initialDepartment;
                    }
                });

                this.$watch('selectedCompany', (value, oldValue) => {
                    // Reset department only if company actually changed
                    if (oldValue && oldValue !== value) {
                        this.selectedDepartment = '';
                        this.customDepartment = '';
                    }
                });
            }
        }
    }
</script>
