@php /** @var \Illuminate\Database\Eloquent\Collection|\App\Models\Company[] $companies */ @endphp
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" x-data="profileForm()">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name">ชื่อ-นามสกุล (Name) <span class="text-red-500">*</span></x-input-label>
            @if($user->role === 'administrator')
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full bg-gray-100 text-gray-500 cursor-not-allowed" :value="old('name', $user->name)" required readonly autocomplete="name" />
                <p class="mt-1 text-sm text-gray-500">บัญชีผู้ดูแลระบบ (Administrator) ไม่สามารถเปลี่ยนชื่อได้</p>
            @else
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="company">บริษัท (Company) <span class="text-red-500">*</span></x-input-label>
            <select id="company" name="company" required x-model="selectedCompany" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="" disabled>เลือกบริษัทของคุณ</option>
                @foreach($companies as $company)
                    <option value="{{ $company->name }}">{{ $company->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('company')" />
        </div>

        <div>
            <x-input-label for="department">แผนก (Department) <span class="text-red-500">*</span></x-input-label>
            <!-- Dropdown สำหรับเมื่อมีแผนก -->
            <div x-show="departments.length > 0 && selectedDepartment !== 'other'" style="display: none;">
                <select id="department_select" x-bind:name="(departments.length > 0 && selectedDepartment !== 'other') ? 'department' : ''" x-model="selectedDepartment" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" :disabled="!selectedCompany" x-bind:required="departments.length > 0 && selectedDepartment !== 'other'">
                    <option value="">กรุณาเลือกแผนกที่ท่านสังกัด</option>
                    <template x-for="dept in departments" :key="dept.id">
                        <option :value="dept.name" x-text="dept.name"></option>
                    </template>
                    <option value="other">อื่นๆ (พิมพ์ระบุเอง)</option>
                </select>
            </div>

            <!-- Input พิมพ์เอง สำหรับเมื่อเลือกอื่นๆ หรือไม่มีแผนก -->
            <div x-show="departments.length === 0 || selectedDepartment === 'other'" style="display: none;" class="relative" :class="{ 'mt-2': departments.length > 0 && selectedDepartment === 'other' }">
                <x-text-input id="department" type="text" x-bind:name="(departments.length === 0 || selectedDepartment === 'other') ? 'department' : ''" x-model="customDepartment" class="mt-1 block w-full" placeholder="ระบุแผนกของคุณ" autocomplete="organization-title" x-bind:disabled="!selectedCompany" x-bind:required="departments.length === 0 || selectedDepartment === 'other'" />
                <button type="button" x-show="departments.length > 0 && selectedDepartment === 'other'" @click="selectedDepartment = ''" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">กลับไปเลือก</button>
            </div>
            
            <p x-show="selectedCompany && departments.length === 0" class="mt-1 text-xs text-gray-500">บริษัทนี้ยังไม่มีการตั้งค่าแผนก กรุณาพิมพ์ระบุเอง</p>
            <p x-show="!selectedCompany" class="mt-1 text-xs text-orange-500">กรุณาเลือกบริษัทก่อน</p>
            <x-input-error class="mt-2" :messages="$errors->get('department')" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('เบอร์โทรศัพท์ (Phone)')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" placeholder="ระบุเบอร์โทรศัพท์ของคุณ (ไม่บังคับ)" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
    
    <script>
        function profileForm() {
            return {
                companies: @json($companies),
                selectedCompany: '{{ old('company', $user->company ?? '') }}',
                selectedDepartment: '',
                customDepartment: '{{ old('department', $user->department ?? '') }}',
                
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

                    this.$watch('selectedCompany', (value, oldValue) => {
                        // Reset department only if company changed manually (not on load)
                        if (oldValue !== undefined && oldValue !== '') {
                            this.selectedDepartment = '';
                            this.customDepartment = '';
                        }
                    });
                }
            }
        }
    </script>
</section>
