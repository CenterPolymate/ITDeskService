@php /** @var \Illuminate\Database\Eloquent\Collection|\App\Models\Company[] $companies */ @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('เพิ่มผู้ใช้งานทีม IT') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('users.store') }}" x-data="userForm()">
                        @csrf
                        
                        <!-- Name -->
                        <div>
                            <x-input-label for="name">ชื่อ-นามสกุล <span class="text-red-500">*</span></x-input-label>
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div class="mt-4">
                            <x-input-label for="email">อีเมล <span class="text-red-500">*</span></x-input-label>
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div class="mt-4">
                            <x-input-label for="password">รหัสผ่าน <span class="text-red-500">*</span></x-input-label>
                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Confirm Password -->
                        <div class="mt-4">
                            <x-input-label for="password_confirmation">ยืนยันรหัสผ่าน <span class="text-red-500">*</span></x-input-label>
                            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>

                        <!-- Role -->
                        <div class="mt-4">
                            <x-input-label for="role">ตำแหน่ง (Role) <span class="text-red-500">*</span></x-input-label>
                            <select id="role" name="role" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="helpdesk" {{ old('role') == 'helpdesk' ? 'selected' : '' }}>Helpdesk (Tier 1)</option>
                                <option value="team_hardware" {{ old('role') == 'team_hardware' ? 'selected' : '' }}>Team Hardware</option>
                                <option value="team_network" {{ old('role') == 'team_network' ? 'selected' : '' }}>Team Network</option>
                                <option value="team_software" {{ old('role') == 'team_software' ? 'selected' : '' }}>Team Software</option>
                                <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Manager (Tier 3)</option>
                                @if(Auth::user()->role === 'administrator')
                                <option value="administrator" {{ old('role') == 'administrator' ? 'selected' : '' }}>Administrator</option>
                                @endif
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <!-- Company -->
                        <div class="mt-4">
                            <x-input-label for="company">บริษัท <span class="text-red-500">*</span></x-input-label>
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
                            <x-input-label for="department" :value="__('หน่วยงาน/แผนก (Department)')" />
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
                            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-8 gap-3">
                            <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                ยกเลิก
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                                บันทึก
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
                selectedCompany: {!! json_encode(old('company', '')) !!},
                selectedDepartment: {!! json_encode(old('department', '')) !!},
                
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
