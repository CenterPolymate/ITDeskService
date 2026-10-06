<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('จัดการผู้ใช้งานทั่วไป') }}
            </h2>
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto" x-data="{ showImportModal: false }">
                <button @click="showImportModal = true" class="inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 w-full sm:w-auto">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Import CSV
                </button>
                <a href="{{ route('normal_users.export', request()->query()) }}" class="inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 w-full sm:w-auto">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 013 3h10a3 3 0 013-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Export CSV
                </a>
                <a href="{{ route('normal_users.create') }}" class="inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 w-full sm:w-auto">
                    + เพิ่มผู้ใช้งานใหม่
                </a>
            </div>

                <!-- Import Modal -->
                <div x-show="showImportModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="showImportModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showImportModal = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div x-show="showImportModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form action="{{ route('normal_users.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">นำเข้าผู้ใช้งานจากไฟล์ CSV</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 mb-4">
                                            ไฟล์ CSV ต้องมีคอลัมน์ตามลำดับดังนี้: <br>
                                            <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">ชื่อ, อีเมล, บริษัท, แผนก, เบอร์โทรศัพท์</code>
                                        </p>
                                        <input type="file" name="csv_file" accept=".csv" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                        นำเข้าข้อมูล
                                    </button>
                                    <button type="button" @click="showImportModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        ยกเลิก
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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
                    <form method="GET" action="{{ route('normal_users.index') }}" class="mb-4 flex flex-col md:flex-row gap-2">
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
                                @if($user->is_active)
                                    <span class="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full bg-green-100 text-green-800 shadow-sm">Active</span>
                                @else
                                    <span class="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full bg-red-100 text-red-800 shadow-sm">Inactive</span>
                                @endif
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
                                <a href="{{ route('normal_users.edit', $user->id) }}" class="flex-1 text-center text-xs text-indigo-700 font-medium hover:text-indigo-900 transition-colors px-2 py-2 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-100">แก้ไข</a>
                                <form action="{{ route('normal_users.destroy', $user->id) }}" method="POST" class="flex-1 m-0 p-0 {{ Auth::id() === $user->id ? 'hidden' : '' }}" onsubmit="return confirm('ยืนยันการทำรายการ?\n(ระบบจะลบถาวรหากไม่มีประวัติ หรือระงับบัญชีหากมีประวัติแล้ว)');">
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
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">อีเมล</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">แผนก</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">บริษัท</th>
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
                                    <td class="px-4 py-4 text-sm {{ $user->is_active ? 'text-gray-500' : 'text-gray-400' }}">{{ $user->department ?? '-' }}</td>
                                    <td class="px-4 py-4 text-sm {{ $user->is_active ? 'text-gray-500' : 'text-gray-400' }}">{{ $user->company }}</td>
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
                                            <a href="{{ route('normal_users.edit', $user->id) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors px-2 py-1 bg-indigo-50 hover:bg-indigo-100 rounded-md">แก้ไข</a>
                                            <form action="{{ route('normal_users.destroy', $user->id) }}" method="POST" class="inline m-0 p-0 {{ Auth::id() === $user->id ? 'invisible' : '' }}" onsubmit="return confirm('ยืนยันการทำรายการ?\n(ระบบจะลบถาวรหากไม่มีประวัติ หรือระงับบัญชีหากมีประวัติแล้ว)');">
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
