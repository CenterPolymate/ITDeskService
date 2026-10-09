<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('จัดการผู้ใช้งานทีม IT') }}
            </h2>
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <a href="{{ route('users.export', request()->query()) }}" class="inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 w-full sm:w-auto">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 013 3h10a3 3 0 013-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Export CSV
                </a>
                <a href="{{ route('users.create') }}" class="inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 w-full sm:w-auto">
                    + เพิ่มผู้ใช้งานใหม่
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                    <form method="GET" action="{{ route('users.index') }}" class="mb-4 flex flex-col md:flex-row gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาชื่อ หรืออีเมล..." class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-1/3 text-sm">
                        <select name="status_filter" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-auto text-sm">
                            <option value="">สถานะทั้งหมด</option>
                            <option value="1" {{ request('status_filter') === '1' ? 'selected' : '' }}>ใช้งาน (Active)</option>
                            <option value="0" {{ request('status_filter') === '0' ? 'selected' : '' }}>ระงับการใช้งาน (Inactive)</option>
                        </select>
                        <button type="submit" class="inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 w-full md:w-auto">ค้นหา</button>
                    </form>

                    <!-- Mobile Card View -->
                    <div class="md:hidden space-y-4 mb-4">
                        @foreach($users as $user)
                        <div class="bg-white border rounded-xl shadow-sm p-4 {{ $user->is_active ? 'border-gray-200' : 'border-red-200 bg-red-50/30' }}">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-bold {{ $user->is_active ? 'text-gray-900' : 'text-gray-500 line-through' }}">{{ $user->name }}</h4>
                                <div class="flex gap-1">
                                    <span class="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full {{ $user->is_active ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-500' }} shadow-sm">
                                        {{ $user->role }}
                                    </span>
                                    @if($user->is_active)
                                        <span class="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full bg-green-100 text-green-800 shadow-sm">Active</span>
                                    @else
                                        <span class="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full bg-red-100 text-red-800 shadow-sm">Inactive</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="text-sm text-gray-600 mb-3 space-y-1">
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <span class="break-all">{{ $user->email }}</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <span>{{ $user->company }} <span class="text-gray-400">/</span> {{ $user->department ?? '-' }}</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="text-[11px] mt-0.5">
                                        @if($user->last_login_at)
                                            เข้าใช้งาน: {{ $user->last_login_at->translatedFormat('d F Y H:i') }}
                                        @else
                                            ไม่เคยเข้าใช้งาน
                                        @endif
                                    </span>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
                                @if(Auth::user()->role === 'administrator')
                                    <form action="{{ route('impersonate', $user->id) }}" method="POST" class="flex-1 m-0 p-0 {{ $user->role === 'administrator' ? 'hidden' : '' }}">
                                        @csrf
                                        <button type="submit" class="w-full text-center text-xs text-emerald-700 font-medium hover:text-emerald-900 transition-colors px-2 py-2 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-100">
                                            Login As
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('users.edit', $user->id) }}" class="flex-1 text-center text-xs text-indigo-700 font-medium hover:text-indigo-900 transition-colors px-2 py-2 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-100">แก้ไข</a>
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="flex-1 m-0 p-0 {{ Auth::id() === $user->id ? 'hidden' : '' }}" onsubmit="return confirm('ยืนยันการทำรายการ?\n(ระบบจะลบถาวรหากไม่มีประวัติ หรือระงับบัญชีหากมีประวัติแล้ว)');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full text-center text-xs text-red-700 font-medium hover:text-red-900 transition-colors px-2 py-2 bg-red-50 hover:bg-red-100 rounded-lg border border-red-100">ลบ</button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden md:block overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <x-sortable-th field="name" label="ชื่อ" />
                                    <x-sortable-th field="email" label="อีเมล" />
                                    <x-sortable-th field="role" label="ตำแหน่ง (Role)" />
                                    <x-sortable-th field="company" label="บริษัท" />
                                    <x-sortable-th field="department" label="แผนก" />
                                    <x-sortable-th field="is_active" label="สถานะ" />
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($users as $user)
                                <tr class="{{ $user->is_active ? '' : 'bg-red-50/50' }}">
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <div class="text-sm font-medium {{ $user->is_active ? 'text-gray-900' : 'text-gray-400 line-through' }}">{{ $user->name }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-sm {{ $user->is_active ? 'text-gray-500' : 'text-gray-400' }}">{{ $user->email }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->is_active ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-500' }}">
                                            {{ $user->role }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm {{ $user->is_active ? 'text-gray-500' : 'text-gray-400' }}">{{ $user->company }}</td>
                                    <td class="px-4 py-2 text-sm {{ $user->is_active ? 'text-gray-500' : 'text-gray-400' }}">{{ $user->department ?: '-' }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        @if($user->is_active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                                        @endif
                                        <div class="text-[11px] text-gray-400 mt-1 whitespace-nowrap">
                                            @if($user->last_login_at)
                                                ใช้งานล่าสุด: <br>{{ $user->last_login_at->translatedFormat('d F Y') }}
                                            @else
                                                ไม่เคยเข้าใช้งาน
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-3">
                                            @if(Auth::user()->role === 'administrator')
                                                <form action="{{ route('impersonate', $user->id) }}" method="POST" class="inline m-0 p-0 {{ $user->role === 'administrator' ? 'invisible' : '' }}">
                                                    @csrf
                                                    <button type="submit" class="text-emerald-600 hover:text-emerald-900 transition-colors px-2 py-1 bg-emerald-50 hover:bg-emerald-100 rounded-md">
                                                        Login As
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('users.edit', $user->id) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors px-2 py-1 bg-indigo-50 hover:bg-indigo-100 rounded-md">แก้ไข</a>
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline m-0 p-0 {{ Auth::id() === $user->id ? 'invisible' : '' }}" onsubmit="return confirm('ยืนยันการทำรายการ?\n(ระบบจะลบถาวรหากไม่มีประวัติ หรือระงับบัญชีหากมีประวัติแล้ว)');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors px-2 py-1 bg-red-50 hover:bg-red-100 rounded-md">ลบ</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
