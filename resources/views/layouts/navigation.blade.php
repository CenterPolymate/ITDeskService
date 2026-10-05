<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo & Title -->
                <div class="shrink-0 flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <div>
                            <img src="{{ asset('images/polymate-logo.png') }}" alt="Polymate Logo" class="h-8 w-auto">
                        </div>
                        <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-700 to-indigo-600 hidden sm:block">
                            ITDeskService
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if(in_array(Auth::user()->role, ['manager', 'administrator']))
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 {{ request()->routeIs('users.*') || request()->routeIs('normal_users.*') ? 'border-b-2 border-indigo-400 text-gray-900 focus:border-indigo-700' : '' }}">
                                        <div>จัดการผู้ใช้งาน</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('users.index')">
                                        {{ __('ทีม IT') }}
                                    </x-dropdown-link>
                                    @if(Auth::user()->role === 'administrator')
                                        <x-dropdown-link :href="route('normal_users.index')">
                                            {{ __('ผู้ใช้งานทั่วไป') }}
                                        </x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif

                    @if(Auth::user()->role === 'administrator')
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 {{ request()->routeIs('categories.*') || request()->routeIs('slas.*') || request()->routeIs('settings.*') || request()->routeIs('audit_logs.*') ? 'border-b-2 border-indigo-400 text-gray-900 focus:border-indigo-700' : '' }}">
                                        <div>ตั้งค่าระบบ (Admin)</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('companies.index')">
                                        {{ __('จัดการรายชื่อบริษัท') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('categories.index')">
                                        {{ __('จัดการหมวดหมู่ปัญหา') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('slas.index')">
                                        {{ __('ตั้งค่า SLA') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('settings.index')">
                                        {{ __('ตั้งค่าระบบ') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('audit_logs.index')">
                                        {{ __('ประวัติการใช้งาน') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('backups.index')">
                                        {{ __('ระบบสำรองข้อมูล (Backup)') }}
                                    </x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">
                <!-- Date -->
                <div class="text-sm font-medium text-gray-500 border-r border-gray-200 pr-4">
                    {{ now()->translatedFormat('d F Y') }}
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="flex items-center gap-2">
                                <span>{{ Auth::user()->name }}</span>
                                <span class="text-[10px] bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full uppercase font-bold tracking-wider">{{ Auth::user()->role }}</span>
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
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

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
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

            @if(in_array(Auth::user()->role, ['manager', 'administrator']))
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">จัดการผู้ใช้งาน</div>
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" class="pl-8">
                    {{ __('ทีม IT') }}
                </x-responsive-nav-link>
                @if(Auth::user()->role === 'administrator')
                    <x-responsive-nav-link :href="route('normal_users.index')" :active="request()->routeIs('normal_users.*')" class="pl-8">
                        {{ __('ผู้ใช้งานทั่วไป') }}
                    </x-responsive-nav-link>
                @endif
            @endif

            @if(Auth::user()->role === 'administrator')
                <div class="px-4 py-2 mt-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">ตั้งค่าระบบ (Admin)</div>
                <x-responsive-nav-link :href="route('companies.index')" :active="request()->routeIs('companies.*')" class="pl-8">
                    {{ __('จัดการรายชื่อบริษัท') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')" class="pl-8">
                    {{ __('จัดการหมวดหมู่ปัญหา') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('slas.index')" :active="request()->routeIs('slas.*')" class="pl-8">
                    {{ __('ตั้งค่า SLA') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*')" class="pl-8">
                    {{ __('ตั้งค่าระบบ') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('audit_logs.index')" :active="request()->routeIs('audit_logs.*')" class="pl-8">
                    {{ __('ประวัติการใช้งาน') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('backups.index')" :active="request()->routeIs('backups.*')" class="pl-8">
                    {{ __('ระบบสำรองข้อมูล (Backup)') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
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
