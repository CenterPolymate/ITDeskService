<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('จัดการผู้ใช้งานทีม IT') }}
            </h2>
            <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                + เพิ่มผู้ใช้งานใหม่
            </a>
        </div>
    </x-slot>

    <div class="py-12">
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
                    <form method="GET" action="{{ route('users.index') }}" class="mb-4 flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาชื่อ หรืออีเมล..." class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-1/3">
                        <select name="status_filter" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">สถานะทั้งหมด</option>
                            <option value="1" {{ request('status_filter') === '1' ? 'selected' : '' }}>ใช้งาน (Active)</option>
                            <option value="0" {{ request('status_filter') === '0' ? 'selected' : '' }}>ระงับการใช้งาน (Inactive)</option>
                        </select>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">ค้นหา</button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">อีเมล</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ตำแหน่ง (Role)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">บริษัท</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">แผนก</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($users as $user)
                                <tr class="{{ $user->is_active ? '' : 'bg-red-50/50' }}">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium {{ $user->is_active ? 'text-gray-900' : 'text-gray-400 line-through' }}">{{ $user->name }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-sm {{ $user->is_active ? 'text-gray-500' : 'text-gray-400' }} break-all max-w-[200px]">{{ $user->email }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->is_active ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-500' }}">
                                            {{ $user->role }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm {{ $user->is_active ? 'text-gray-500' : 'text-gray-400' }}">{{ $user->company }}</td>
                                    <td class="px-4 py-4 text-sm {{ $user->is_active ? 'text-gray-500' : 'text-gray-400' }}">{{ $user->department ?: '-' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">
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
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
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
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline m-0 p-0 {{ Auth::id() === $user->id ? 'invisible' : '' }}" onsubmit="return confirm('ยืนยันการระงับบัญชีนี้?');">
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
