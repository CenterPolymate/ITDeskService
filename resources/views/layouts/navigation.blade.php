<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-[1550px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo & Title -->
                <div class="shrink-0 flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                        <div>
                            <img src="{{ $branding['app_logo'] ?? asset('images/polymate-logo.png') }}" alt="{{ $branding['app_name'] ?? 'ITDeskService' }}" class="h-8 max-w-[140px] w-auto object-contain">
                        </div>
                        <div class="flex flex-col text-left">
                            <span class="text-base sm:text-lg font-bold text-indigo-600 whitespace-nowrap leading-tight">
                                {{ $branding['app_name'] ?? 'ITDeskService' }}
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:-my-px sm:ms-5 lg:ms-8 sm:flex sm:items-center sm:gap-1.5 lg:gap-3 xl:gap-5">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @php
                        $user = Auth::user();
                        $isManagerOrAdmin = $user && ($user->isManager() || $user->role === 'administrator');
                        $isAdmin = $user && $user->role === 'administrator';
                    @endphp

                    @if($isManagerOrAdmin)
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                            {{ __('รายงานสถิติ') }}
                        </x-nav-link>
                    @endif

                    @if($isManagerOrAdmin || $user?->role === 'helpdesk')
                        <div class="hidden sm:flex sm:items-center">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-2.5 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 whitespace-nowrap {{ request()->routeIs('users.*') || request()->routeIs('normal_users.*') ? 'border-b-2 border-indigo-400 text-gray-900 focus:border-indigo-700' : '' }}">
                                        <span>จัดการผู้ใช้งาน</span>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    @if($isManagerOrAdmin)
                                        <x-dropdown-link :href="route('users.index')">
                                            {{ __('ทีม IT') }}
                                        </x-dropdown-link>
                                    @endif
                                    <x-dropdown-link :href="route('normal_users.index')">
                                        {{ __('ผู้ใช้งานทั่วไป') }}
                                    </x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif

                    @if($isManagerOrAdmin)
                        <div class="hidden sm:flex sm:items-center">
                            <x-dropdown align="left" width="60">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-2.5 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 whitespace-nowrap {{ request()->routeIs('categories.*') || request()->routeIs('teams.*') || request()->routeIs('holidays.*') || request()->routeIs('companies.*') || request()->routeIs('settings.*') || request()->routeIs('audit_logs.*') || request()->routeIs('backups.*') ? 'border-b-2 border-indigo-400 text-gray-900 focus:border-indigo-700' : '' }}">
                                        <span>{{ $isAdmin ? 'ตั้งค่าระบบ (Admin)' : 'ตั้งค่าระบบ' }}</span>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <!-- Group 1: การจัดการบริการและองค์กร (Service & Organization) -->
                                    <div class="px-3 pt-2 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                        <span>บริการและองค์กร</span>
                                        <span class="text-[10px] font-medium text-indigo-600 lowercase px-1.5 py-0.5 rounded bg-indigo-50 border border-indigo-100">Service</span>
                                    </div>

                                    <x-dropdown-link :href="route('companies.index')">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                            <span>{{ __('จัดการรายชื่อบริษัท') }}</span>
                                        </span>
                                    </x-dropdown-link>

                                    <x-dropdown-link :href="route('teams.index')">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                            <span>{{ __('จัดการทีมงานและสายงาน') }}</span>
                                        </span>
                                    </x-dropdown-link>

                                    <x-dropdown-link :href="route('categories.index')">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                            <span>{{ __('จัดการหมวดหมู่ปัญหา') }}</span>
                                        </span>
                                    </x-dropdown-link>

                                    <x-dropdown-link :href="route('holidays.index')">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            <span>{{ __('ตั้งค่าวันหยุด (Holidays)') }}</span>
                                        </span>
                                    </x-dropdown-link>

                                    <!-- Divider -->
                                    <div class="my-1.5 border-t border-slate-100"></div>

                                    <!-- Section 2: ระบบและความปลอดภัย (System & Security) -->
                                    <div class="px-3 pt-1 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                        <span>ระบบและความปลอดภัย</span>
                                        <span class="text-[10px] font-medium text-slate-500 lowercase px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200">System</span>
                                    </div>

                                    <x-dropdown-link :href="route('audit_logs.index')">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                            <span>{{ __('ประวัติการใช้งาน') }}</span>
                                        </span>
                                    </x-dropdown-link>

                                    @if($isAdmin)
                                        <x-dropdown-link :href="route('settings.index')">
                                            <span class="flex items-center gap-2.5">
                                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                <span>{{ __('ตั้งค่าระบบ') }}</span>
                                            </span>
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('backups.index')">
                                            <span class="flex items-center gap-2.5">
                                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                                                <span>{{ __('ระบบสำรองข้อมูล (Backup)') }}</span>
                                            </span>
                                        </x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:gap-2 sm:ms-4 shrink-0">
                <!-- Sound Notification Control Dropdown (Desktop) -->
                <div x-data="{
                    dropdownOpen: false,
                    soundEnabled: true,
                    permission: 'default',
                    selectedTone: 'chime',
                    init() {
                        this.syncState();
                        window.addEventListener('itdesk:sound-toggled', (e) => {
                            this.soundEnabled = !!e.detail.enabled;
                        });
                        window.addEventListener('itdesk:sound-tone-changed', (e) => {
                            this.selectedTone = e.detail.tone;
                        });
                    },
                    syncState() {
                        if (window.ITDeskNotification) {
                            this.soundEnabled = window.ITDeskNotification.isSoundEnabled();
                            this.permission = window.ITDeskNotification.getNotificationPermission();
                            this.selectedTone = window.ITDeskNotification.getSelectedSoundTone();
                        } else {
                            const stored = localStorage.getItem('itdesk_sound_enabled');
                            this.soundEnabled = stored === null ? true : stored === 'true';
                            this.permission = (typeof Notification !== 'undefined') ? Notification.permission : 'denied';
                            this.selectedTone = localStorage.getItem('itdesk_sound_tone') || 'chime';
                        }
                    },
                    toggle() {
                        if (window.ITDeskNotification) {
                            this.soundEnabled = window.ITDeskNotification.toggleSound();
                        } else {
                            this.soundEnabled = !this.soundEnabled;
                            localStorage.setItem('itdesk_sound_enabled', this.soundEnabled ? 'true' : 'false');
                            window.dispatchEvent(new CustomEvent('itdesk:sound-toggled', { detail: { enabled: this.soundEnabled } }));
                        }
                    },
                    changeTone(tone) {
                        this.selectedTone = tone;
                        if (window.ITDeskNotification) {
                            window.ITDeskNotification.setSelectedSoundTone(tone);
                            window.ITDeskNotification.playNotificationTone(tone, true);
                        } else {
                            localStorage.setItem('itdesk_sound_tone', tone);
                        }
                    },
                    testSound() {
                        if (window.ITDeskNotification) {
                            window.ITDeskNotification.playNotificationTone(this.selectedTone, true);
                        }
                    },
                    async requestPermission() {
                        if (window.ITDeskNotification) {
                            this.permission = await window.ITDeskNotification.requestNotificationPermission();
                        } else if (typeof Notification !== 'undefined') {
                            this.permission = await Notification.requestPermission();
                        }
                    }
                }" @click.outside="dropdownOpen = false" class="relative">
                    <button
                        type="button"
                        @click="dropdownOpen = !dropdownOpen; syncState();"
                        :title="soundEnabled ? 'ระบบเสียงแจ้งเตือน: เปิดอยู่ (คลิกเพื่อเลือกเสียง/ทดสอบ/ตั้งค่า)' : 'ระบบเสียงแจ้งเตือน: ปิดอยู่ (คลิกเพื่อเปิด)'"
                        class="relative p-2 rounded-xl border transition-all duration-200 shadow-xs focus:outline-none flex items-center justify-center shrink-0 cursor-pointer"
                        :class="soundEnabled 
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100 hover:border-emerald-400' 
                            : 'bg-slate-100 text-slate-500 border-slate-300 hover:bg-slate-200 hover:text-slate-800'"
                    >
                        <template x-if="soundEnabled">
                            <span class="relative flex items-center justify-center">
                                <span class="animate-ping absolute -top-1 -right-1 inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="absolute -top-1 -right-1 inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </span>
                        </template>
                        <template x-if="!soundEnabled">
                            <span class="relative flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                                </svg>
                            </span>
                        </template>
                    </button>

                    <!-- Dropdown Panel -->
                    <div
                        x-show="dropdownOpen"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-[340px] sm:w-[360px] bg-white rounded-2xl shadow-2xl border border-slate-200 p-4 z-50 text-slate-800"
                        style="display: none; width: 360px; min-width: 320px; max-width: calc(100vw - 2rem);"
                    >
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 flex items-center gap-1.5 whitespace-nowrap">
                                    <span>🔔 ระบบเสียงแจ้งเตือน</span>
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5 whitespace-nowrap">แจ้งเตือนเคสตามบทบาทและแชทใหม่</p>
                            </div>
                            <button type="button" @click="dropdownOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Toggle Switch -->
                        <div class="py-3 flex items-center justify-between gap-3 border-b border-slate-100">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-800 whitespace-nowrap">เสียงแจ้งเตือนทุกระบบ</div>
                                <div class="text-[11px] text-slate-500 whitespace-nowrap">เปิดเป็นค่าเริ่มต้นทุกผู้ใช้งาน</div>
                            </div>
                            <button
                                type="button"
                                @click="toggle()"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="soundEnabled ? 'bg-emerald-600' : 'bg-slate-300'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                    :class="soundEnabled ? 'translate-x-5' : 'translate-x-0'"
                                ></span>
                            </button>
                        </div>

                        <!-- Sound Tone Selector & Preview -->
                        <div class="py-3 border-b border-slate-100 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <label for="sound-tone-select-desktop" class="text-xs font-bold text-slate-800 flex items-center gap-1 whitespace-nowrap">
                                    <span>🎵 รูปแบบเสียง (Sound Tone)</span>
                                </label>
                                <span class="text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-medium border border-emerald-200 whitespace-nowrap shrink-0">ปรับเสียงให้ดัง & ยาวขึ้น</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1 min-w-0">
                                    <select 
                                        id="sound-tone-select-desktop"
                                        x-model="selectedTone" 
                                        @change="changeTone(selectedTone)"
                                        class="w-full text-xs border-slate-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-1.5 pl-2.5 pr-8 font-medium text-slate-700 bg-slate-50 cursor-pointer truncate"
                                    >
                                        <option value="chime">🔔 กระดิ่งกังวาน 3 โน้ต (ดังชัดเจน - แนะนำ)</option>
                                        <option value="melody">✨ ท่วงทำนอง 4 โน้ต (นุ่มนวล พรีเมียม)</option>
                                        <option value="desk_bell">🛎️ กระดิ่งเคาน์เตอร์ Service Desk (คมชัด)</option>
                                        <option value="radar">🚨 สัญญาณเรดาร์เตือน (ฉุกเฉิน ตื่นตัว)</option>
                                        <option value="classic">🎵 คลาสสิก ดิ่ง-ด่อง (ปรับเสียงดังขึ้น)</option>
                                    </select>
                                </div>
                                <button
                                    type="button"
                                    @click="changeTone(selectedTone)"
                                    title="กดฟังตัวอย่างเสียงนี้"
                                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shrink-0 transition flex items-center gap-1 shadow-xs cursor-pointer whitespace-nowrap"
                                >
                                    <span>▶</span>
                                    <span>ฟัง</span>
                                </button>
                            </div>
                            <p class="text-[10px] text-slate-400">เลือกเสียงแล้วระบบจะเล่นให้ฟังทันที และจำค่าไว้บนเบราว์เซอร์นี้</p>
                        </div>

                        <!-- Browser Push Notification Card -->
                        <div class="pt-3">
                            <div class="text-[11px] text-slate-600 mb-2">
                                <span class="font-bold">การแจ้งเตือนป๊อปอัป (Browser):</span>
                            </div>
                            <template x-if="permission === 'granted'">
                                <div class="flex items-center gap-1.5 p-2 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-medium">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>อนุญาตการแจ้งเตือนป๊อปอัปแล้ว</span>
                                </div>
                            </template>
                            <template x-if="permission === 'default'">
                                <button
                                    type="button"
                                    @click="requestPermission()"
                                    class="w-full flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <span>เปิดรับการแจ้งเตือนบนหน้าจอ</span>
                                </button>
                            </template>
                            <template x-if="permission === 'denied'">
                                <div class="p-2 bg-slate-50 border border-slate-200 text-slate-500 rounded-xl text-[11px]">
                                    ⚠️ เบราว์เซอร์ถูกบล็อกแจ้งเตือน (สามารถกดอนุญาตได้ที่รูปกุญแจ 🔒 หน้ารายการ URL)
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- User Dropdown Menu -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-2.5 py-1.5 border border-transparent rounded-xl text-gray-500 bg-white hover:text-gray-700 hover:bg-slate-50 focus:outline-none transition ease-in-out duration-150 whitespace-nowrap">
                            <div class="flex flex-col text-left">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-800 leading-tight">{{ Auth::user()?->name }}</span>
                                    <span class="text-[9px] bg-indigo-100 text-indigo-800 px-1.5 py-0.5 rounded-full uppercase font-bold tracking-wider leading-none">{{ Auth::user()?->role }}</span>
                                </div>
                                <span class="text-[11px] text-gray-400 font-normal leading-tight mt-0.5">
                                    {{ now()->locale('th')->translatedFormat('d F Y') }}
                                </span>
                            </div>

                            <div class="ms-2">
                                <svg class="fill-current h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger & Mobile Sound Control -->
            <div class="-me-2 flex items-center gap-1.5 sm:hidden">
                <!-- Mobile Sound Toggle Button -->
                <div x-data="{
                    soundEnabled: true,
                    init() {
                        this.syncState();
                        window.addEventListener('itdesk:sound-toggled', (e) => {
                            this.soundEnabled = !!e.detail.enabled;
                        });
                    },
                    syncState() {
                        if (window.ITDeskNotification) {
                            this.soundEnabled = window.ITDeskNotification.isSoundEnabled();
                        } else {
                            const stored = localStorage.getItem('itdesk_sound_enabled');
                            this.soundEnabled = stored === null ? true : stored === 'true';
                        }
                    },
                    toggle() {
                        if (window.ITDeskNotification) {
                            this.soundEnabled = window.ITDeskNotification.toggleSound();
                            window.ITDeskNotification.playNotificationChime(true);
                        } else {
                            this.soundEnabled = !this.soundEnabled;
                            localStorage.setItem('itdesk_sound_enabled', this.soundEnabled ? 'true' : 'false');
                            window.dispatchEvent(new CustomEvent('itdesk:sound-toggled', { detail: { enabled: this.soundEnabled } }));
                        }
                    }
                }" class="relative">
                    <button
                        type="button"
                        @click="toggle()"
                        :title="soundEnabled ? 'ระบบเสียงแจ้งเตือน: เปิดอยู่ (แตะเพื่อสลับ)' : 'ระบบเสียงแจ้งเตือน: ปิดอยู่ (แตะเพื่อสลับ)'"
                        class="p-2 rounded-lg border text-xs font-semibold transition shadow-xs focus:outline-none"
                        :class="soundEnabled ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-300'"
                    >
                        <template x-if="soundEnabled">
                            <span class="relative flex items-center justify-center">
                                <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-emerald-400 opacity-75"></span>
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </span>
                        </template>
                        <template x-if="!soundEnabled">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                        </template>
                    </button>
                </div>

                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if($isManagerOrAdmin)
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    {{ __('รายงานสถิติ') }}
                </x-responsive-nav-link>
            @endif

            @if($isManagerOrAdmin || $user?->role === 'helpdesk')
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">จัดการผู้ใช้งาน</div>
                @if($isManagerOrAdmin)
                    <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" class="pl-8">
                        {{ __('ทีม IT') }}
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('normal_users.index')" :active="request()->routeIs('normal_users.*')" class="pl-8">
                    {{ __('ผู้ใช้งานทั่วไป') }}
                </x-responsive-nav-link>
            @endif

            @if($isManagerOrAdmin)
                <div class="px-4 pt-3 pb-1 text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between border-t border-slate-100 mt-2">
                    <span>บริการและองค์กร (Service)</span>
                </div>
                <x-responsive-nav-link :href="route('companies.index')" :active="request()->routeIs('companies.*')" class="pl-8">
                    {{ __('จัดการรายชื่อบริษัท') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('teams.index')" :active="request()->routeIs('teams.*')" class="pl-8">
                    {{ __('จัดการทีมงานและสายงาน') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')" class="pl-8">
                    {{ __('จัดการหมวดหมู่ปัญหา') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('holidays.index')" :active="request()->routeIs('holidays.*')" class="pl-8">
                    {{ __('ตั้งค่าวันหยุด (Holidays)') }}
                </x-responsive-nav-link>

                <div class="px-4 pt-3 pb-1 text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between border-t border-slate-100 mt-2">
                    <span>ระบบและความปลอดภัย (System)</span>
                </div>
                <x-responsive-nav-link :href="route('audit_logs.index')" :active="request()->routeIs('audit_logs.*')" class="pl-8">
                    {{ __('ประวัติการใช้งาน (Audit Logs)') }}
                </x-responsive-nav-link>

                @if($isAdmin)
                    <x-responsive-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*')" class="pl-8">
                        {{ __('ตั้งค่าระบบ (Settings)') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('backups.index')" :active="request()->routeIs('backups.*')" class="pl-8">
                        {{ __('ระบบสำรองข้อมูล (Backup)') }}
                    </x-responsive-nav-link>
                @endif
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()?->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()?->email }}</div>
                <div class="text-[11px] text-gray-400 font-normal mt-0.5">{{ now()->locale('th')->translatedFormat('d F Y') }}</div>
            </div>

            <!-- Mobile Sound Notification Settings -->
            <div x-data="{
                soundEnabled: true,
                selectedTone: 'chime',
                init() {
                    this.syncState();
                    window.addEventListener('itdesk:sound-toggled', (e) => { this.soundEnabled = !!e.detail.enabled; });
                    window.addEventListener('itdesk:sound-tone-changed', (e) => { this.selectedTone = e.detail.tone; });
                },
                syncState() {
                    if (window.ITDeskNotification) {
                        this.soundEnabled = window.ITDeskNotification.isSoundEnabled();
                        this.selectedTone = window.ITDeskNotification.getSelectedSoundTone();
                    } else {
                        const stored = localStorage.getItem('itdesk_sound_enabled');
                        this.soundEnabled = stored === null ? true : stored === 'true';
                        this.selectedTone = localStorage.getItem('itdesk_sound_tone') || 'chime';
                    }
                },
                toggle() {
                    if (window.ITDeskNotification) {
                        this.soundEnabled = window.ITDeskNotification.toggleSound();
                    } else {
                        this.soundEnabled = !this.soundEnabled;
                        localStorage.setItem('itdesk_sound_enabled', this.soundEnabled ? 'true' : 'false');
                        window.dispatchEvent(new CustomEvent('itdesk:sound-toggled', { detail: { enabled: this.soundEnabled } }));
                    }
                },
                changeTone(tone) {
                    this.selectedTone = tone;
                    if (window.ITDeskNotification) {
                        window.ITDeskNotification.setSelectedSoundTone(tone);
                        window.ITDeskNotification.playNotificationTone(tone, true);
                    } else {
                        localStorage.setItem('itdesk_sound_tone', tone);
                    }
                }
            }" class="mx-4 my-2 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <span>🔔 เสียงแจ้งเตือน</span>
                    </span>
                    <button
                        type="button"
                        @click="toggle()"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="soundEnabled ? 'bg-emerald-600' : 'bg-slate-300'"
                    >
                        <span
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                            :class="soundEnabled ? 'translate-x-4' : 'translate-x-0'"
                        ></span>
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <select
                        x-model="selectedTone"
                        @change="changeTone(selectedTone)"
                        class="w-full text-xs border-slate-300 rounded-lg py-1.5 pl-2.5 pr-8 bg-white text-slate-700 cursor-pointer"
                    >
                        <option value="chime">🔔 กระดิ่งกังวาน 3 โน้ต (ดังชัดเจน)</option>
                        <option value="melody">✨ ท่วงทำนอง 4 โน้ต (พรีเมียม)</option>
                        <option value="desk_bell">🛎️ กระดิ่งเคาน์เตอร์ (คมชัด)</option>
                        <option value="radar">🚨 สัญญาณเรดาร์เตือน</option>
                        <option value="classic">🎵 คลาสสิก ดิ่ง-ด่อง</option>
                    </select>
                    <button
                        type="button"
                        @click="changeTone(selectedTone)"
                        class="px-2.5 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-bold shrink-0 cursor-pointer"
                    >
                        ▶ ฟัง
                    </button>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
