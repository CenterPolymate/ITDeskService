<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-indigo-50 border border-indigo-100 text-indigo-600 rounded-xl shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 tracking-tight">
                        {{ __('ประวัติการใช้งานระบบ (Audit Logs)') }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">ติดตามการเปลี่ยนแปลง เพิ่ม แก้ไข และลบข้อมูลทั้งหมดภายในระบบ</p>
                </div>
            </div>
            
            <nav class="flex text-sm font-medium" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center text-gray-400">ตั้งค่าระบบ</li>
                    <li>
                        <div class="flex items-center text-gray-400">
                            <svg class="w-4 h-4 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <span class="text-indigo-600">Audit Logs</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Filters & Export -->
            <div class="bg-white shadow-sm sm:rounded-xl border border-gray-100 p-4 mb-6">
                <form method="GET" action="{{ route('audit_logs.index') }}" class="flex flex-col md:flex-row md:items-end gap-4">
                    <div class="w-full md:w-1/4">
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">ตั้งแต่วันที่</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div class="w-full md:w-1/4">
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">ถึงวันที่</label>
                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div class="w-full md:w-1/4">
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">ผู้ใช้งาน</label>
                        <select name="user_id" id="user_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">ทั้งหมด</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full md:w-1/4">
                        <label for="action_filter" class="block text-sm font-medium text-gray-700 mb-1">การกระทำ</label>
                        <select name="action_filter" id="action_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">ทั้งหมด</option>
                            <option value="POST" {{ request('action_filter') == 'POST' ? 'selected' : '' }}>เพิ่มข้อมูล</option>
                            <option value="PUT" {{ request('action_filter') == 'PUT' ? 'selected' : '' }}>แก้ไขข้อมูล</option>
                            <option value="DELETE" {{ request('action_filter') == 'DELETE' ? 'selected' : '' }}>ลบข้อมูล</option>
                        </select>
                    </div>
                    <div class="flex gap-2 w-full md:w-auto">
                        <button type="submit" class="w-full md:w-auto inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            ค้นหา
                        </button>
                        <a href="{{ route('audit_logs.index') }}" class="w-full md:w-auto inline-flex justify-center items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                            ล้าง
                        </a>
                        <button type="submit" name="export" value="csv" class="w-full md:w-auto inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            CSV
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-lg shadow-gray-200/50 sm:rounded-2xl border border-gray-100">
                <div class="p-6 text-gray-900">
                    <div class="w-full">
                        @if($logs->count() > 0)
                        
                        <!-- Mobile Card View -->
                        <div class="md:hidden space-y-4 mb-4">
                            @foreach($logs as $log)
                                @php
                                    $userName = $log->user->name ?? 'System';
                                    $actionText = $log->action;
                                    $badgeClass = 'bg-gray-100 text-gray-800';
                                    if (str_contains($log->action, 'impersonate.leave')) {
                                        $actionText = 'คืนสิทธิ์ (Leave)';
                                        $badgeClass = 'bg-purple-50 text-purple-700 ring-purple-600/20';
                                    } elseif (str_contains($log->action, 'impersonate')) {
                                        $actionText = 'จำลองสิทธิ์';
                                        $badgeClass = 'bg-purple-50 text-purple-700 ring-purple-600/20';
                                    } elseif (str_contains($log->action, 'POST') || $log->action === 'created') {
                                        $actionText = 'เพิ่มข้อมูล';
                                        $badgeClass = 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
                                    } elseif (str_contains($log->action, 'PUT') || str_contains($log->action, 'PATCH') || $log->action === 'updated') {
                                        $actionText = 'แก้ไขข้อมูล';
                                        $badgeClass = 'bg-blue-50 text-blue-700 ring-blue-600/20';
                                    } elseif (str_contains($log->action, 'DELETE') || $log->action === 'deleted') {
                                        $actionText = 'ลบข้อมูล';
                                        $badgeClass = 'bg-rose-50 text-rose-700 ring-rose-600/20';
                                    }

                                    $targetText = class_basename($log->target_type);
                                    if (str_contains($log->target_type, 'categories')) $targetText = 'หมวดหมู่ปัญหา';
                                    elseif (str_contains($log->target_type, 'companies')) $targetText = 'ข้อมูลบริษัท';
                                    elseif (str_contains($log->target_type, 'users')) $targetText = 'ผู้ใช้งาน';
                                    elseif (str_contains($log->target_type, 'departments')) $targetText = 'แผนก/หน่วยงาน';
                                    elseif (str_contains($log->target_type, 'slas')) $targetText = 'SLA';
                                    elseif (str_contains($log->target_type, 'holidays')) $targetText = 'วันหยุดนักขัตฤกษ์';
                                    elseif (str_contains($log->target_type, 'settings')) $targetText = 'ตั้งค่าระบบ';
                                    elseif (str_contains($log->target_type, 'impersonate')) $targetText = 'บัญชีผู้ใช้';
                                    
                                    $displayId = $log->target_id;
                                    if ($displayId) {
                                        $decoded = json_decode($displayId, true);
                                        if (is_array($decoded) && isset($decoded['id'])) {
                                            $displayId = $decoded['id'];
                                        } elseif (strlen($displayId) > 15) {
                                            $displayId = substr($displayId, 0, 15) . '...';
                                        }
                                    }
                                @endphp
                                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 relative">
                                    <div class="flex justify-between items-start mb-3 border-b border-gray-100 pb-2">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">{{ $log->created_at->translatedFormat('d M Y') }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5">{{ $log->created_at->format('H:i') }} น.</div>
                                        </div>
                                        <span class="px-2.5 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full ring-1 ring-inset {{ $badgeClass }} whitespace-nowrap">{{ $actionText }}</span>
                                    </div>
                                    
                                    <div class="space-y-2 mb-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-100 to-blue-100 border border-indigo-200 flex items-center justify-center text-indigo-700 font-bold text-[10px] shrink-0">
                                                {{ mb_substr($userName, 0, 1) }}
                                            </div>
                                            <span class="text-xs text-gray-700"><span class="text-gray-500">โดย:</span> {{ $userName }}</span>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                            <span class="text-xs text-gray-700">
                                                <span class="text-gray-500">เป้าหมาย:</span> {{ $targetText }}
                                                @if($displayId) <span class="text-gray-400">#{{ $displayId }}</span> @endif
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <div class="text-[11px] font-semibold text-gray-500 uppercase mb-2">รายละเอียด</div>
                                        <div class="text-xs text-gray-600">
                                            @if(str_contains($log->target_type, 'impersonate'))
                                                @if(str_contains($log->action, 'impersonate.leave'))
                                                    <span class="text-gray-700">กลับสู่บัญชีหลัก (Admin)</span>
                                                @else
                                                    @php
                                                        $targetUser = \App\Models\User::find((int)$displayId);
                                                        $userName = $targetUser ? $targetUser->name : "ID: $displayId";
                                                    @endphp
                                                    <span class="text-gray-700">เข้าสู่ระบบด้วยสิทธิ์ของ: <span class="font-medium text-indigo-700">{{ $userName }}</span></span>
                                                @endif
                                            @elseif($log->changes && is_array($log->changes))
                                                @php
                                                    $keyMap = [
                                                        'name' => 'ชื่อ',
                                                        'name_th' => 'ชื่อ (ไทย)',
                                                        'short_name' => 'ชื่อย่อ',
                                                        'is_active' => 'สถานะการใช้งาน',
                                                        'status' => 'สถานะ',
                                                        'description' => 'รายละเอียด',
                                                        'email_domains' => 'โดเมนอีเมล',
                                                        'role' => 'ระดับสิทธิ์',
                                                        'company' => 'บริษัท',
                                                        'department' => 'แผนก/หน่วยงาน',
                                                        'phone' => 'เบอร์โทรศัพท์',
                                                        'priority' => 'ความเร่งด่วน',
                                                        'hours' => 'จำนวนชั่วโมง',
                                                        'sla_type' => 'ประเภท SLA',
                                                        'date' => 'วันที่',
                                                    ];
                                                @endphp
                                                <div class="grid grid-cols-1 gap-2">
                                                    @php $ignoredKeys = ['id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at', 'remember_token', 'password']; @endphp
                                                    @foreach($log->changes as $key => $value)
                                                        @if(in_array($key, $ignoredKeys)) @continue @endif
                                                        @php
                                                            $keyName = $keyMap[$key] ?? $key;
                                                            $newValStr = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
                                                            if ($newValStr === '') $newValStr = 'ว่างเปล่า';
                                                            elseif (in_array($key, ['is_active', 'status'])) $newValStr = $value == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                                                        @endphp
                                                        <div class="text-sm border-b border-gray-100 last:border-0 pb-2 last:pb-0">
                                                            @if(str_contains($log->action, 'DELETE') || $log->action === 'deleted')
                                                                <span class="font-semibold text-gray-700">{{ $keyName }}:</span> <span class="text-gray-600">{{ $newValStr }}</span>
                                                            @elseif(str_contains($log->action, 'POST') || $log->action === 'created')
                                                                <span class="font-semibold text-gray-700">{{ $keyName }}:</span> <span class="text-gray-600">{{ $newValStr }}</span>
                                                            @else
                                                                @if($log->old_values && array_key_exists($key, $log->old_values))
                                                                    @php
                                                                        $oldVal = $log->old_values[$key];
                                                                        $oldValStr = is_array($oldVal) ? json_encode($oldVal, JSON_UNESCAPED_UNICODE) : (string) $oldVal;
                                                                        if ($oldValStr === '') $oldValStr = 'ว่างเปล่า';
                                                                        elseif (in_array($key, ['is_active', 'status'])) $oldValStr = $oldVal == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                                                                    @endphp
                                                                    <div class="font-semibold text-blue-700 mb-1">{{ $keyName }}:</div>
                                                                    <div class="pl-2 border-l-2 border-gray-200 text-gray-600 space-y-1">
                                                                        <div><span class="text-xs text-gray-500 uppercase">เดิม:</span> <span class="text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded break-words">{{ $oldValStr }}</span></div>
                                                                        <div><span class="text-xs text-gray-500 uppercase">ใหม่:</span> <span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-medium break-words">{{ $newValStr }}</span></div>
                                                                    </div>
                                                                @else
                                                                    <span class="font-semibold text-gray-700">{{ $keyName }}:</span> <span class="text-gray-600">{{ $newValStr }}</span>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif($log->changes)
                                                <pre class="text-[10px] max-w-full overflow-x-auto whitespace-pre-wrap">{{ is_string($log->changes) ? $log->changes : json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @else
                                                <em class="text-gray-400">ไม่มีรายละเอียด</em>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Desktop Table View -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">เวลา</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">ผู้ใช้งาน</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">การกระทำ</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">เป้าหมาย</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">รายละเอียด</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    @foreach($logs as $log)
                                    <tr class="hover:bg-indigo-50/30 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">{{ $log->created_at->translatedFormat('d M Y') }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5">{{ $log->created_at->format('H:i') }} น.</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                @php $userName = $log->user->name ?? 'System'; @endphp
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-100 to-blue-100 border border-indigo-200 flex items-center justify-center text-indigo-700 font-bold text-xs">
                                                    {{ mb_substr($userName, 0, 1) }}
                                                </div>
                                                <span class="text-sm font-medium text-gray-900">{{ $userName }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php
                                                $actionText = $log->action;
                                                $badgeClass = 'bg-gray-100 text-gray-800';
                                                if (str_contains($log->action, 'impersonate.leave')) {
                                                    $actionText = 'คืนสิทธิ์ (Leave)';
                                                    $badgeClass = 'bg-purple-50 text-purple-700 ring-purple-600/20';
                                                } elseif (str_contains($log->action, 'impersonate')) {
                                                    $actionText = 'จำลองสิทธิ์';
                                                    $badgeClass = 'bg-purple-50 text-purple-700 ring-purple-600/20';
                                                } elseif (str_contains($log->action, 'POST') || $log->action === 'created') {
                                                    $actionText = 'เพิ่มข้อมูล';
                                                    $badgeClass = 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
                                                } elseif (str_contains($log->action, 'PUT') || str_contains($log->action, 'PATCH') || $log->action === 'updated') {
                                                    $actionText = 'แก้ไขข้อมูล';
                                                    $badgeClass = 'bg-blue-50 text-blue-700 ring-blue-600/20';
                                                } elseif (str_contains($log->action, 'DELETE') || $log->action === 'deleted') {
                                                    $actionText = 'ลบข้อมูล';
                                                    $badgeClass = 'bg-rose-50 text-rose-700 ring-rose-600/20';
                                                }
                                            @endphp
                                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ring-1 ring-inset {{ $badgeClass }}">{{ $actionText }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php
                                                $targetText = class_basename($log->target_type);
                                                if (str_contains($log->target_type, 'categories')) $targetText = 'หมวดหมู่ปัญหา';
                                                elseif (str_contains($log->target_type, 'companies')) $targetText = 'ข้อมูลบริษัท';
                                                elseif (str_contains($log->target_type, 'users')) $targetText = 'ผู้ใช้งาน';
                                                elseif (str_contains($log->target_type, 'departments')) $targetText = 'แผนก/หน่วยงาน';
                                                elseif (str_contains($log->target_type, 'slas')) $targetText = 'SLA';
                                                elseif (str_contains($log->target_type, 'holidays')) $targetText = 'วันหยุดนักขัตฤกษ์';
                                                elseif (str_contains($log->target_type, 'settings')) $targetText = 'ตั้งค่าระบบ';
                                                elseif (str_contains($log->target_type, 'impersonate')) $targetText = 'บัญชีผู้ใช้';
                                            @endphp
                                            {{ $targetText }} 
                                            @if($log->target_id) 
                                                @php
                                                    $displayId = $log->target_id;
                                                    $decoded = json_decode($displayId, true);
                                                    if (is_array($decoded) && isset($decoded['id'])) {
                                                        $displayId = $decoded['id'];
                                                    } elseif (strlen($displayId) > 15) {
                                                        $displayId = substr($displayId, 0, 15) . '...';
                                                    }
                                                @endphp
                                                <span class="text-gray-400">#{{ $displayId }}</span> 
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            @if(str_contains($log->target_type, 'impersonate'))
                                                @if(str_contains($log->action, 'impersonate.leave'))
                                                    <span class="text-gray-700">กลับสู่บัญชีหลัก (Admin)</span>
                                                @else
                                                    @php
                                                        $targetUser = \App\Models\User::find((int)$displayId);
                                                        $userName = $targetUser ? $targetUser->name : "ID: $displayId";
                                                    @endphp
                                                    <span class="text-gray-700">เข้าสู่ระบบด้วยสิทธิ์ของ: <span class="font-medium text-indigo-700">{{ $userName }}</span></span>
                                                @endif
                                            @elseif($log->changes && is_array($log->changes))
                                                @php
                                                    $keyMap = [
                                                        'name' => 'ชื่อ',
                                                        'name_th' => 'ชื่อ (ไทย)',
                                                        'short_name' => 'ชื่อย่อ',
                                                        'is_active' => 'สถานะการใช้งาน',
                                                        'status' => 'สถานะ',
                                                        'description' => 'รายละเอียด',
                                                        'email_domains' => 'โดเมนอีเมล',
                                                        'role' => 'ระดับสิทธิ์',
                                                        'company' => 'บริษัท',
                                                        'department' => 'แผนก/หน่วยงาน',
                                                        'phone' => 'เบอร์โทรศัพท์',
                                                        'priority' => 'ความเร่งด่วน',
                                                        'hours' => 'จำนวนชั่วโมง',
                                                        'sla_type' => 'ประเภท SLA',
                                                        'date' => 'วันที่',
                                                    ];
                                                @endphp
                                                <div class="grid grid-cols-1 gap-2">
                                                    @php $ignoredKeys = ['id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at', 'remember_token', 'password']; @endphp
                                                    @foreach($log->changes as $key => $value)
                                                        @if(in_array($key, $ignoredKeys)) @continue @endif
                                                        @php
                                                            $keyName = $keyMap[$key] ?? $key;
                                                            $newValStr = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
                                                            if ($newValStr === '') $newValStr = 'ว่างเปล่า';
                                                            elseif (in_array($key, ['is_active', 'status'])) $newValStr = $value == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                                                        @endphp
                                                        <div class="text-sm">
                                                            @if(str_contains($log->action, 'DELETE') || $log->action === 'deleted')
                                                                <span class="font-medium text-gray-700">{{ $keyName }}:</span> <span class="text-gray-600">{{ $newValStr }}</span>
                                                            @elseif(str_contains($log->action, 'POST') || $log->action === 'created')
                                                                <span class="font-medium text-gray-700">{{ $keyName }}:</span> <span class="text-gray-600">{{ $newValStr }}</span>
                                                            @else
                                                                @if($log->old_values && array_key_exists($key, $log->old_values))
                                                                    @php
                                                                        $oldVal = $log->old_values[$key];
                                                                        $oldValStr = is_array($oldVal) ? json_encode($oldVal, JSON_UNESCAPED_UNICODE) : (string) $oldVal;
                                                                        if ($oldValStr === '') $oldValStr = 'ว่างเปล่า';
                                                                        elseif (in_array($key, ['is_active', 'status'])) $oldValStr = $oldVal == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                                                                    @endphp
                                                                    <div class="flex flex-col mb-1.5">
                                                                        <span class="font-medium text-blue-700">{{ $keyName }}:</span>
                                                                        <div class="flex items-center flex-wrap gap-1.5 mt-0.5 text-[13px]">
                                                                            <span class="text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded break-all">"{{ $oldValStr }}"</span>
                                                                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                                                            <span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-medium break-all">"{{ $newValStr }}"</span>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <span class="font-medium text-gray-700">{{ $keyName }}:</span> <span class="text-gray-600">{{ $newValStr }}</span>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif($log->changes)
                                                <pre class="text-xs max-w-xs overflow-x-auto whitespace-pre-wrap">{{ is_string($log->changes) ? $log->changes : json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @else
                                                <em class="text-gray-400">ไม่มีรายละเอียด</em>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
                            <div class="w-24 h-24 mb-4 text-gray-200">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">ไม่มีประวัติการใช้งาน</h3>
                            <p class="mt-1 text-sm text-gray-500 max-w-sm">
                                ยังไม่มีการบันทึกประวัติการเปลี่ยนแปลงใดๆ หรือไม่พบข้อมูลตรงตามเงื่อนไขที่ค้นหา
                            </p>
                        </div>
                        @endif
                    </div>
                    <div class="mt-4 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                        <div class="w-full sm:w-auto flex-1">
                            {{ $logs->links() }}
                        </div>
                        <div class="flex items-center space-x-2 sm:ml-4">
                            <label for="per_page" class="text-sm text-gray-700">แสดง</label>
                            <select id="per_page" class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 py-1.5 pl-3 pr-8" onchange="window.location.href='?per_page='+this.value">
                                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                <option value="30" {{ $perPage == 30 ? 'selected' : '' }}>30</option>
                                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                            </select>
                            <span class="text-sm text-gray-700">รายการต่อหน้า</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
