@php /** @var \Illuminate\Database\Eloquent\Collection|\App\Models\Company[] $companies */ @endphp
@php /** @var \App\Models\User $normal_user */ @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('แก้ไขผู้ใช้งานทั่วไป') }}
            </h2>
            <form action="{{ route('normal_users.force_reset_password', $normal_user->id) }}" method="POST" class="inline" onsubmit="return confirm('ระบบจะส่งลิงก์ตั้งรหัสผ่านใหม่ไปที่อีเมลนี้ ยืนยันหรือไม่?');">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                    ส่งลิงก์รีเซ็ตรหัสผ่าน
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('normal_users.update', $normal_user->id) }}" x-data="userForm()">
                        @csrf
                        @method('PUT')
                        
                        <!-- Name -->
                        <div>
                            <x-input-label for="name" :value="__('ชื่อ-นามสกุล')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $normal_user->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div class="mt-4">
                            <x-input-label for="email" :value="__('อีเมล')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $normal_user->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div class="mt-4">
                            <x-input-label for="password" :value="__('รหัสผ่านใหม่ (เว้นว่างไว้หากไม่ต้องการเปลี่ยน)')" />
                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Confirm Password -->
                        <div class="mt-4">
                            <x-input-label for="password_confirmation" :value="__('ยืนยันรหัสผ่านใหม่ (เว้นว่างไว้หากไม่ต้องการเปลี่ยน)')" />
                            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>

                        <!-- Role -->
                        <div class="mt-4">
                            <x-input-label for="role">ตำแหน่ง (Role) <span class="text-red-500">*</span></x-input-label>
                            <select id="role" name="role" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="user" {{ old('role', $normal_user->role) == 'user' ? 'selected' : '' }}>User ทั่วไป</option>
                                <option value="helpdesk" {{ old('role', $normal_user->role) == 'helpdesk' ? 'selected' : '' }}>Helpdesk (Tier 1)</option>
                                <option value="team_hardware" {{ old('role', $normal_user->role) == 'team_hardware' ? 'selected' : '' }}>Team Hardware</option>
                                <option value="team_network" {{ old('role', $normal_user->role) == 'team_network' ? 'selected' : '' }}>Team Network</option>
                                <option value="team_software" {{ old('role', $normal_user->role) == 'team_software' ? 'selected' : '' }}>Team Software</option>
                                <option value="manager" {{ old('role', $normal_user->role) == 'manager' ? 'selected' : '' }}>Manager (Tier 3)</option>
                                @if(Auth::user()->role === 'administrator')
                                <option value="administrator" {{ old('role', $normal_user->role) == 'administrator' ? 'selected' : '' }}>Administrator</option>
                                @endif
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <!-- Company -->
                        <div class="mt-4">
                            <x-input-label for="company" :value="__('บริษัท')" />
                            <select id="company" name="company" x-model="selectedCompany" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">เลือกบริษัท</option>
                                <template x-for="comp in companies" :key="comp.id">
                                    <option :value="comp.name" x-text="comp.name"></option>
                                </template>
                            </select>
                            <x-input-error :messages="$errors->get('company')" class="mt-2" />
                        </div>

                        <!-- Department -->
                        <div class="mt-4">
                            <x-input-label for="department" :value="__('แผนก (ไม่บังคับ)')" />
                            <select id="department" name="department" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" :disabled="!selectedCompany || departments.length === 0" :value="selectedDepartment" @change="selectedDepartment = $event.target.value">
                                <option value="">เลือกแผนก</option>
                                <template x-for="dept in departments" :key="dept.id">
                                    <option :value="dept.name" x-text="dept.name" :selected="dept.name === selectedDepartment"></option>
                                </template>
                            </select>
                            <p x-show="selectedCompany && departments.length === 0" class="mt-1 text-sm text-gray-500">บริษัทนี้ยังไม่มีการตั้งค่าแผนก</p>
                            <x-input-error :messages="$errors->get('department')" class="mt-2" />
                        </div>

                        <!-- Phone -->
                        <div class="mt-4">
                            <x-input-label for="phone" :value="__('เบอร์โทรศัพท์ (ไม่บังคับ)')" />
                            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $normal_user->phone)" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <!-- Status (is_active) -->
                        <div class="mt-6 flex items-center">
                            <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $normal_user->is_active)) class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900 font-medium">
                                บัญชีนี้เปิดใช้งานอยู่ (Active)
                            </label>
                        </div>

                        <div class="flex items-center justify-end mt-8 gap-3">
                            <a href="{{ route('normal_users.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                ยกเลิก
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                                บันทึกการแก้ไข
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function userForm() {
            return {
                companies: @json($companies),
                selectedCompany: {!! json_encode(old('company', $normal_user->company ?? '')) !!},
                selectedDepartment: {!! json_encode(old('department', $normal_user->department ?? '')) !!},
                
                get departments() {
                    if (!this.selectedCompany) return [];
                    const company = this.companies.find(c => c.name === this.selectedCompany);
                    return company && company.departments ? company.departments : [];
                },
                
                init() {
                    this.$watch('selectedCompany', (value, oldValue) => {
                        // Reset department only if company actually changed
                        if (oldValue && oldValue !== value) {
                            this.selectedDepartment = '';
                        }
                    });
                }
            }
        }
    </script>
</x-app-layout>
