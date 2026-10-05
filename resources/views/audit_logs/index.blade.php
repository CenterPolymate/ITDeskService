<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('ประวัติการใช้งานระบบ (Audit Logs)') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10 outline outline-1 outline-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">เวลา</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">ผู้ใช้งาน</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">การกระทำ</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">เป้าหมาย</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">ข้อมูลที่เปลี่ยนแปลง</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($logs as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $log->created_at->translatedFormat('d F Y H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $log->user->name ?? 'System' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @php
                                            $actionText = $log->action;
                                            $badgeClass = 'bg-gray-100 text-gray-800';
                                            if (str_contains($log->action, 'POST') || $log->action === 'created') {
                                                $actionText = 'เพิ่มข้อมูล';
                                                $badgeClass = 'bg-green-100 text-green-800';
                                            } elseif (str_contains($log->action, 'PUT') || str_contains($log->action, 'PATCH') || $log->action === 'updated') {
                                                $actionText = 'แก้ไขข้อมูล';
                                                $badgeClass = 'bg-blue-100 text-blue-800';
                                            } elseif (str_contains($log->action, 'DELETE') || $log->action === 'deleted') {
                                                $actionText = 'ลบข้อมูล';
                                                $badgeClass = 'bg-red-100 text-red-800';
                                            }
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badgeClass }}">{{ $actionText }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @php
                                            $targetText = class_basename($log->target_type);
                                            if (str_contains($log->target_type, 'categories')) $targetText = 'หมวดหมู่ปัญหา';
                                            elseif (str_contains($log->target_type, 'companies')) $targetText = 'ข้อมูลบริษัท';
                                            elseif (str_contains($log->target_type, 'users')) $targetText = 'ผู้ใช้งาน';
                                            elseif (str_contains($log->target_type, 'departments')) $targetText = 'แผนก/หน่วยงาน';
                                            elseif (str_contains($log->target_type, 'slas')) $targetText = 'SLA';
                                            elseif (str_contains($log->target_type, 'settings')) $targetText = 'ตั้งค่าระบบ';
                                        @endphp
                                        {{ $targetText }} 
                                        @if($log->target_id) 
                                            <span class="text-gray-400">#{{ $log->target_id }}</span> 
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        @if($log->changes && is_array($log->changes))
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
                                                ];
                                            @endphp
                                            <div class="grid grid-cols-1 gap-1">
                                                @foreach($log->changes as $key => $value)
                                                    <div class="flex space-x-2">
                                                        <span class="font-medium text-gray-700">{{ $keyMap[$key] ?? $key }}:</span>
                                                        <span class="text-blue-600 break-all">
                                                            @if(is_array($value))
                                                                {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                                            @elseif(is_null($value) || $value === '')
                                                                <em class="text-gray-400">ว่างเปล่า</em>
                                                            @elseif(in_array($key, ['is_active', 'status']))
                                                                @if($value == 1)
                                                                    <span class="text-green-600">เปิดใช้งาน</span>
                                                                @else
                                                                    <span class="text-red-600">ปิดใช้งาน</span>
                                                                @endif
                                                            @else
                                                                {{ $value }}
                                                            @endif
                                                        </span>
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
                    <div class="mt-4 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                        <div class="flex items-center space-x-2">
                            <label for="per_page" class="text-sm text-gray-700">แสดง</label>
                            <select id="per_page" class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 py-1.5 pl-3 pr-8" onchange="window.location.href='?per_page='+this.value">
                                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                <option value="30" {{ $perPage == 30 ? 'selected' : '' }}>30</option>
                                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                            </select>
                            <span class="text-sm text-gray-700">รายการต่อหน้า</span>
                        </div>
                        <div class="w-full sm:w-auto">
                            {{ $logs->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
