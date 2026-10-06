<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('รายละเอียดใบแจ้งซ่อม (Ticket Details) #') . $ticket->ticket_no }}
                @if($ticket->pcar_no)
                    <span class="ml-2 text-sm text-gray-500 font-normal">| PCAR #{{ $ticket->pcar_no }}</span>
                @endif
            </h2>
            <div class="flex items-center space-x-4 print:hidden">
                @if(auth()->user()->role !== 'user')
                    <a href="{{ route('tickets.print', $ticket->id) }}" target="_blank" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        พิมพ์ใบงาน (Print)
                    </a>
                @endif
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    &larr; กลับไปยัง Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- ฝั่งซ้าย: ข้อมูลรายละเอียดเคส (2 ส่วน) -->
                @php
                    $user = Auth::user();
                    $isAssignedTeam = false;
                    if ($ticket->escalated_to_team && str_contains($user->role, 'team_')) {
                        $teamMap = ['team_hardware' => 'Hardware', 'team_network' => 'Network', 'team_software' => 'Software'];
                        if (isset($teamMap[$user->role]) && $teamMap[$user->role] === $ticket->escalated_to_team) {
                            $isAssignedTeam = true;
                        }
                    }
                    if (is_null($ticket->escalated_to_team) && $user->role === 'helpdesk') {
                        $isAssignedTeam = true;
                    }
                @endphp
                <div class="md:col-span-2 space-y-6">
                    
                    <!-- ข้อมูลทั่วไป -->
                    @if($ticket->status === 'cancelled')
                    <div class="bg-gray-100 border-l-4 border-gray-500 p-4 rounded-md shadow-sm mb-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-medium text-gray-800">ใบงานนี้ถูกยกเลิกแล้ว</h3>
                                <div class="mt-2 text-sm text-gray-700">
                                    <p><strong>เหตุผล:</strong> {{ $ticket->cancellation_reason ?: 'ไม่ได้ระบุเหตุผล' }}</p>
                                    <p class="mt-1 text-gray-500 text-xs">
                                        ยกเลิกเมื่อ {{ $ticket->cancelled_at ? $ticket->cancelled_at->translatedFormat('d F Y H:i') : '-' }} 
                                        โดย {{ $ticket->cancelledBy ? $ticket->cancelledBy->name : 'Unknown' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- SLA Visualizer -->
                    @if($ticket->sla_due_at && !in_array($ticket->status, ['closed', 'cancelled']))
                    <div x-data="slaCountdown('{{ $ticket->sla_due_at->toIso8601String() }}')" class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded-md shadow-sm mb-6 flex justify-between items-center transition-colors duration-500" :class="{ 'bg-red-50 border-red-500': isOverdue }">
                        <div>
                            <h3 class="text-sm font-bold text-indigo-800" :class="{ 'text-red-800': isOverdue }">กำหนดส่งมอบงาน (SLA Due)</h3>
                            <p class="text-xs text-indigo-600 mt-1" :class="{ 'text-red-600': isOverdue }">ภายใน: {{ $ticket->sla_due_at->translatedFormat('d F Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black text-indigo-700 font-mono tracking-wider" :class="{ 'text-red-700': isOverdue }" x-text="timeLeft">--:--:--</span>
                            <div class="text-xs font-semibold uppercase mt-1" :class="isOverdue ? 'text-red-500 animate-pulse' : 'text-indigo-500'" x-text="isOverdue ? 'Overdue (เกินกำหนด)' : 'Remaining (เวลาที่เหลือ)'"></div>
                        </div>
                    </div>
                    @endif

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="mb-6 border-b pb-4">
                            <h3 class="text-2xl font-bold text-gray-900">หัวข้อปัญหา: {{ $ticket->title }}</h3>
                            <p class="text-sm text-gray-500 mt-1">แจ้งเมื่อ: {{ $ticket->created_at->translatedFormat('d F Y H:i') }} ({{ $ticket->created_at->diffForHumans() }})</p>
                        </div>

                        <div class="prose max-w-none mb-6">
                            <h4 class="text-md font-semibold text-gray-700">รายละเอียดอาการ:</h4>
                            <p class="text-gray-600 whitespace-pre-wrap mt-2 bg-gray-50 p-4 rounded-md border">{{ $ticket->description }}</p>
                        </div>

                        <!-- รูปภาพหลักฐาน (ถ้ามี) -->
                        @if($ticket->attachments->count() > 0)
                        <div class="mt-6">
                            <h4 class="text-md font-semibold text-gray-700 mb-3">รูปภาพ/ไฟล์แนบ ({{ $ticket->attachments->count() }} รูป):</h4>
                            <div class="flex flex-wrap gap-4">
                                @foreach($ticket->attachments as $attachment)
                                <div class="border rounded-md overflow-hidden bg-gray-50 p-2 inline-block">
                                    <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $attachment->file_path) }}" alt="Attachment" class="max-w-full h-auto max-h-96 object-contain rounded hover:opacity-90 transition-opacity">
                                    </a>
                                    <p class="text-xs text-center text-gray-500 mt-2">คลิกเพื่อดูรูปขนาดเต็ม</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @elseif($ticket->attachment_path)
                        <div class="mt-6">
                            <h4 class="text-md font-semibold text-gray-700 mb-3">รูปภาพ/ไฟล์แนบ:</h4>
                            <div class="border rounded-md overflow-hidden bg-gray-50 p-2 inline-block">
                                <a href="{{ asset('storage/' . $ticket->attachment_path) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $ticket->attachment_path) }}" alt="Attachment" class="max-w-full h-auto max-h-96 object-contain rounded hover:opacity-90 transition-opacity">
                                </a>
                                <p class="text-xs text-center text-gray-500 mt-2">คลิกเพื่อดูรูปขนาดเต็ม</p>
                            </div>
                        </div>
                        @endif

                        <!-- บันทึกการสืบสภาพและวิเคราะห์ -->
                        @if($ticket->analysis_notes && is_array($ticket->analysis_notes) && Auth::user()->role !== 'user')
                        <div class="mt-6 border-t pt-4">
                            <h4 class="text-md font-semibold text-purple-700 mb-3">การวิเคราะห์หาสาเหตุ (Root Cause Analysis):</h4>
                            <div class="bg-purple-50 p-4 rounded-md border border-purple-200 text-sm space-y-3">
                                @if(!empty($ticket->analysis_notes['root_cause']))
                                    <div class="flex gap-2">
                                        <span class="font-bold text-purple-900 w-40 shrink-0 whitespace-nowrap">สาเหตุของปัญหาเบื้องต้น :</span>
                                        <span class="text-gray-900 font-medium">{{ $ticket->analysis_notes['root_cause'] }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- บันทึกการแก้ไขปัญหาเฉพาะหน้า -->
                        @if($ticket->resolution_notes || $ticket->parts->count() > 0)
                        <div class="mt-6 border-t pt-4">
                            @if($ticket->resolution_notes)
                            <h4 class="text-md font-semibold text-green-700 mb-2">บันทึกการแก้ไขปัญหาเฉพาะหน้า (Resolution Notes):</h4>
                            <p class="text-gray-700 whitespace-pre-wrap bg-green-50 p-4 rounded-md border border-green-200">{{ $ticket->resolution_notes }}</p>
                            @endif
                            
                            @if($ticket->parts->count() > 0)
                            <div class="mt-4">
                                <h4 class="text-sm font-semibold text-green-700 mb-2">รายการอะไหล่ / วัสดุอุปกรณ์ที่ใช้:</h4>
                                <div class="bg-white border border-gray-200 rounded-md overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm whitespace-nowrap">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-4 py-2 text-left font-medium text-gray-500 w-12">ลำดับ</th>
                                                <th scope="col" class="px-4 py-2 text-left font-medium text-gray-500">ชื่ออะไหล่/วัสดุ</th>
                                                <th scope="col" class="px-4 py-2 text-right font-medium text-gray-500">จำนวน</th>
                                                <th scope="col" class="px-4 py-2 text-left font-medium text-gray-500">หน่วยนับ</th>
                                                <th scope="col" class="px-4 py-2 text-left font-medium text-gray-500">เอกสารเบิก/ใบขอซื้อ</th>
                                                <th scope="col" class="px-4 py-2 text-left font-medium text-gray-500">ซื้อจากบริษัท</th>
                                                <th scope="col" class="px-4 py-2 text-right font-medium text-gray-500">ราคา/หน่วย</th>
                                                <th scope="col" class="px-4 py-2 text-right font-medium text-gray-500">รวม</th>
                                                <th scope="col" class="px-4 py-2 text-left font-medium text-gray-500">หมายเหตุ</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            @php $grandTotal = 0; @endphp
                                            @foreach($ticket->parts as $index => $part)
                                            @php 
                                                $itemTotal = $part->unit_price ? $part->quantity * $part->unit_price : 0;
                                                $grandTotal += $itemTotal;
                                            @endphp
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2 text-gray-500">{{ $index + 1 }}</td>
                                                <td class="px-4 py-2 font-medium text-gray-900">{{ $part->part_name }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ $part->quantity }}</td>
                                                <td class="px-4 py-2 text-gray-700">{{ $part->unit }}</td>
                                                <td class="px-4 py-2 text-gray-500">{{ $part->document_no ?: '-' }}</td>
                                                <td class="px-4 py-2 text-gray-500">{{ $part->vendor ?: '-' }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ $part->unit_price ? number_format($part->unit_price, 2) : '-' }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ $itemTotal ? number_format($itemTotal, 2) : '-' }}</td>
                                                <td class="px-4 py-2 text-gray-500">{{ $part->remarks ?: '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        @if($grandTotal > 0)
                                        <tfoot class="bg-gray-50">
                                            <tr>
                                                <td colspan="7" class="px-4 py-2 text-right font-bold text-gray-700">ยอดรวมทั้งหมด:</td>
                                                <td class="px-4 py-2 text-right font-bold text-green-700">{{ number_format($grandTotal, 2) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                        <!-- ฟอร์มแก้ไขข้อมูลแบบ Inline -->
                        @if(($isAssignedTeam || ($user->role === 'helpdesk' && is_null($ticket->escalated_to_team))) && in_array($ticket->status, ['in_progress', 'resolved']))
                        <div x-data="{ editMode: false }" class="mt-4 pt-4 border-t">
                            <div class="flex justify-end">
                                <button type="button" @click="editMode = !editMode" class="text-sm font-medium text-amber-600 hover:text-amber-800 flex items-center gap-1 bg-amber-50 px-3 py-1.5 rounded-md border border-amber-200 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    <span x-text="editMode ? 'ยกเลิกการแก้ไข' : 'แก้ไขบันทึกการซ่อม'"></span>
                                </button>
                            </div>
                            
                            <div x-show="editMode" x-cloak class="mt-4 p-5 border border-amber-200 bg-amber-50/50 rounded-lg shadow-inner">
                                <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="update_notes">
                                    
                                    <h4 class="font-bold text-gray-800 text-sm mb-4 pb-2 border-b border-amber-200">แก้ไขข้อมูลการวิเคราะห์และแก้ไขปัญหา</h4>
                                    
                                    <div class="space-y-3 mb-6">
                                        <div class="flex items-center gap-3">
                                            <label class="w-24 text-sm font-bold text-gray-800 shrink-0 mt-2">ทำไม 1 :</label>
                                            <input type="text" name="why_1" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm" value="{{ $ticket->why_1 }}" />
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <label class="w-24 text-sm font-bold text-gray-800 shrink-0 mt-2">ทำไม 2 :</label>
                                            <input type="text" name="why_2" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm" value="{{ $ticket->why_2 }}" />
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <label class="w-24 text-sm font-bold text-gray-800 shrink-0 mt-2">ทำไม 3 :</label>
                                            <input type="text" name="why_3" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm" value="{{ $ticket->why_3 }}" />
                                        </div>
                                        <div class="flex items-start gap-3 mt-2 pt-2 border-t border-amber-100">
                                            <label class="w-32 text-sm font-bold text-gray-800 shrink-0 mt-2">สาเหตุรากเหง้า :</label>
                                            <textarea name="root_cause_detail" required rows="3" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">{{ $ticket->root_cause_detail }}</textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-5">
                                        <label for="resolution_notes" class="block font-bold text-gray-800 text-sm mb-2">แก้ไขบันทึกการแก้ไขปัญหาเฉพาะหน้า</label>
                                        <textarea name="resolution_notes" id="resolution_notes" rows="3" {{ $ticket->status === 'resolved' ? 'required' : '' }} class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">{{ $ticket->resolution_notes }}</textarea>
                                    </div>
                                    

                                    
                                    <div class="flex justify-end gap-3">
                                        <button type="submit" class="flex justify-center py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors">
                                            💾 บันทึกการแก้ไข
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Action Form for Assigned Team (Tier 2) -->
                    @if($isAssignedTeam && in_array($ticket->status, ['assigned', 'analyzing', 'in_progress']))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-emerald-500">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            การดำเนินการ (Action)
                        </h3>

                        <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            @if($ticket->status === 'assigned')
                                <input type="hidden" name="action" value="start_analyzing">
                                    <div class="text-center py-6">
                                        <p class="text-gray-600 mb-4">คุณได้รับมอบหมายงานนี้ กรุณากดปุ่มด้านล่างเพื่อเริ่มต้นกระบวนการสืบสภาพและวิเคราะห์หาสาเหตุ</p>
                                        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                                            🔍 เริ่มดำเนินการสืบสภาพ
                                        </button>
                                    </div>
                                @elseif($ticket->status === 'analyzing')
                                    <div class="mb-4 space-y-3">
                                        <h4 class="font-bold text-gray-700 text-sm mb-3 border-b pb-2">การวิเคราะห์หาสาเหตุ</h4>
                                        
                                        <div class="space-y-3">
                                            <div class="flex items-center gap-3">
                                                <label class="w-16 font-bold text-gray-700 text-sm shrink-0">ทำไม 1 :</label>
                                                <input type="text" name="why_1" class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm sm:text-sm" value="{{ $ticket->why_1 }}" />
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <label class="w-16 font-bold text-gray-700 text-sm shrink-0">ทำไม 2 :</label>
                                                <input type="text" name="why_2" class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm sm:text-sm" value="{{ $ticket->why_2 }}" />
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <label class="w-16 font-bold text-gray-700 text-sm shrink-0">ทำไม 3 :</label>
                                                <input type="text" name="why_3" class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm sm:text-sm" value="{{ $ticket->why_3 }}" />
                                            </div>
                                            <div class="flex flex-col gap-2 mt-2 pt-2 border-t border-gray-100">
                                                <label class="text-sm font-semibold text-gray-700 whitespace-nowrap">สาเหตุรากเหง้า :</label>
                                                <textarea name="root_cause_detail" required rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $ticket->root_cause_detail }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="action" value="update_analysis" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                        💾 บันทึก
                                    </button>
                                    <p class="text-xs text-gray-500 mt-2 text-center">* หากระบุสาเหตุของปัญหาเบื้องต้น ระบบจะจบบันทึกเวลาสืบสภาพและเริ่มขั้นตอนแก้ไขทันที</p>
                                @elseif($ticket->status === 'in_progress')
                                    @if(!empty($ticket->root_cause_detail))
                                    <div class="mb-4 bg-gray-50 p-3 rounded-md border border-gray-200">
                                        <h4 class="text-xs font-bold text-gray-500 mb-1">การวิเคราะห์หาสาเหตุ:</h4>
                                        <ul class="text-sm text-gray-800 list-disc ml-4">
                                            @if($ticket->why_1)<li><strong>ทำไม 1:</strong> {{ $ticket->why_1 }}</li>@endif
                                            @if($ticket->why_2)<li><strong>ทำไม 2:</strong> {{ $ticket->why_2 }}</li>@endif
                                            @if($ticket->why_3)<li><strong>ทำไม 3:</strong> {{ $ticket->why_3 }}</li>@endif
                                        </ul>
                                        <div class="mt-2 text-sm text-gray-800">
                                            <strong>สาเหตุรากเหง้า:</strong> {{ $ticket->root_cause_detail }}
                                        </div>
                                    </div>
                                    @endif
                                    <input type="hidden" name="action" value="resolve">
                                    <div class="mb-4">
                                        <label for="resolution_notes" class="block font-bold text-gray-700 text-sm mb-2">บันทึกการแก้ไขปัญหาเฉพาะหน้า (Resolution Notes)</label>
                                        <textarea name="resolution_notes" id="resolution_notes" rows="3" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" placeholder="ระบุรายละเอียดว่าแก้ไขปัญหาอย่างไร..."></textarea>
                                    </div>

                                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                                        ✅ บันทึกและส่งมอบงาน
                                    </button>
                                @endif
                        </form>
                    </div>
                    @endif

                    @php
                        $isRequester = Auth::user()->email === $ticket->requester_email;
                        $isHelpdesk = Auth::user()->role === 'helpdesk';
                        $showAcceptanceForm = ($isRequester || $isHelpdesk) && $ticket->status === 'resolved';
                    @endphp

                    <!-- Action Form for Acceptance (Requester/Helpdesk) -->
                    @if($showAcceptanceForm)
                    @php
                        $targetTime = $ticket->resolved_at ? $ticket->resolved_at->copy()->addMinutes(5) : now();
                    @endphp
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-amber-500"
                         x-data="{
                             targetTime: new Date('{{ $targetTime->toIso8601String() }}').getTime(),
                             now: new Date().getTime(),
                             isRequester: {{ $isRequester ? 'true' : 'false' }},
                             timer: null,
                             get timeLeftMs() {
                                 return Math.max(0, this.targetTime - this.now);
                             },
                             get canSubmit() {
                                 return this.isRequester || this.timeLeftMs === 0;
                             },
                             get formattedTime() {
                                 let totalSeconds = Math.floor(this.timeLeftMs / 1000);
                                 let m = Math.floor(totalSeconds / 60);
                                 let s = totalSeconds % 60;
                                 return m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0');
                             },
                             init() {
                                 this.timer = setInterval(() => {
                                     this.now = new Date().getTime();
                                     if (this.timeLeftMs === 0) {
                                         clearInterval(this.timer);
                                     }
                                 }, 1000);
                             }
                         }">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            รับทราบการแก้ไข (Accept Resolution)
                        </h3>
                        <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST" x-data="{ open: false, rating: 5, hoverRating: 0 }">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="accept_resolution">
                            <input type="hidden" name="rating" :value="rating">
                            
                            <p class="text-sm text-gray-600 mb-4">การแก้ไขเสร็จสิ้นแล้ว กรุณากดยืนยันการรับทราบการแก้ไข</p>
                            
                            <button type="button" @click="open = true" 
                                    x-bind:disabled="!canSubmit" 
                                    x-bind:class="canSubmit ? 'bg-amber-500 hover:bg-amber-600' : 'bg-gray-400 cursor-not-allowed'"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors">
                                ✅ ยืนยันรับทราบและปิดใบงาน
                                
                                @if($isHelpdesk && !$isRequester)
                                    <template x-if="!canSubmit">
                                        <span>&nbsp;(กดแทนได้ในอีก <span x-text="formattedTime"></span> นาที)</span>
                                    </template>
                                @endif
                            </button>

                            <!-- CSAT Modal -->
                            <div x-show="open" 
                                 x-cloak
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                                <div @click.away="open = false" class="bg-white rounded-lg p-6 w-full max-w-md mx-4 shadow-xl">
                                    <h4 class="text-lg font-bold text-gray-900 mb-2 text-center">คุณพึงพอใจกับการบริการมากน้อยเพียงใด?</h4>
                                    <p class="text-xs text-gray-500 mb-4 text-center">การประเมินของคุณจะช่วยให้เราพัฒนาบริการให้ดียิ่งขึ้น</p>
                                    
                                    <!-- Star Rating -->
                                    <div class="flex justify-center gap-2 mb-6">
                                        <template x-for="i in 5">
                                            <button type="button" 
                                                    @click="rating = i" 
                                                    @mouseenter="hoverRating = i" 
                                                    @mouseleave="hoverRating = 0"
                                                    class="focus:outline-none transition-transform hover:scale-110">
                                                <svg class="w-10 h-10 transition-colors duration-150" 
                                                     :class="{ 'text-yellow-400': hoverRating >= i || (!hoverRating && rating >= i), 'text-gray-300': !(hoverRating >= i || (!hoverRating && rating >= i)) }" 
                                                     fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                    
                                    <div class="mb-4 text-left">
                                        <label for="feedback" class="block font-medium text-sm text-gray-700 mb-1">ข้อเสนอแนะเพิ่มเติม (ถ้ามี)</label>
                                        <textarea id="feedback" name="feedback" rows="3" class="block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm" placeholder="พิมพ์ข้อเสนอแนะ หรือคำชมเชยที่นี่..."></textarea>
                                    </div>
                                    
                                    <div class="flex justify-end gap-3 mt-6">
                                        <button type="button" @click="open = false" class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                            ยกเลิก
                                        </button>
                                        <button type="submit" class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-500 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                            ยืนยันและส่งคะแนน
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endif

                    <!-- Action Form for Manager Review -->
                    @if($ticket->status === 'closed' && Auth::user()->role === 'manager')
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-gray-800">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            การตรวจสอบจากหัวหน้า (Manager Review)
                        </h3>
                        <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="close">
                            
                            <div x-data="{ requiresPreventive: '{{ $ticket->requires_preventive_measure === true ? 1 : ($ticket->requires_preventive_measure === false ? 0 : '') }}' }">
                                <div class="mb-4">
                                    <label class="block font-bold text-gray-700 text-sm mb-2">มาตรการป้องกัน (Preventive Measure)</label>
                                    <div class="flex items-center space-x-4">
                                        <label class="inline-flex items-center">
                                            <input type="radio" class="form-radio text-gray-800" name="requires_preventive_measure" value="1" required x-model="requiresPreventive">
                                            <span class="ml-2">ต้องการมาตรการป้องกัน</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" class="form-radio text-gray-800" name="requires_preventive_measure" value="0" required x-model="requiresPreventive">
                                            <span class="ml-2">ไม่ต้องการมาตรการป้องกัน</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg" x-show="requiresPreventive == '1'" style="display: none;">
                                    <label class="block font-bold text-gray-700 text-sm mb-1">ส่งต่อให้ทีมเฉพาะทาง (Tier 2) ดำเนินการ Task 2 <span class="text-red-500">*</span></label>
                                    <select name="escalated_to_team" class="block w-full border-gray-300 focus:border-gray-800 focus:ring-gray-800 rounded-md shadow-sm" x-bind:required="requiresPreventive == '1'">
                                        <option value="" disabled {{ is_null($ticket->escalated_to_team) ? 'selected' : '' }}>เลือกทีม...</option>
                                        <option value="team_hardware" {{ $ticket->escalated_to_team === 'team_hardware' ? 'selected' : '' }}>ช่างฮาร์ดแวร์ (Hardware Team)</option>
                                        <option value="team_network" {{ $ticket->escalated_to_team === 'team_network' ? 'selected' : '' }}>ช่างเครือข่าย (Network Team)</option>
                                        <option value="team_software" {{ $ticket->escalated_to_team === 'team_software' ? 'selected' : '' }}>ช่างซอฟต์แวร์ (Software Team)</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">ใบงานนี้จะถูกส่งไปยัง Dashboard ของทีมที่เลือก เพื่อให้ดำเนินการวิเคราะห์ P-CAR ต่อไป</p>
                                </div>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-4">สถานะเคสนี้คือปิดใบงานแล้ว หัวหน้าสามารถบันทึกหรือแก้ไขข้อมูลมาตรการป้องกันได้ที่นี่</p>
                            
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
                                💾 บันทึกการตรวจสอบ
                            </button>
                        </form>
                    </div>
                    @endif

                    <!-- Task 2: Preventive Action Form (For IT Staff) -->
                    @if($ticket->requires_preventive_measure === true && Auth::user()->role !== 'user')
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-teal-500 mb-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Task 2: การป้องกันปัญหาไม่ให้เกิดซ้ำ (Preventive Action)
                        </h3>
                        
                        @if($ticket->preventive_measure === 'assigned')
                            <div class="text-center py-6 bg-teal-50 rounded-lg border border-teal-100">
                                <p class="text-teal-800 font-medium mb-4">ผู้จัดการได้มอบหมายงาน Task 2 ให้กับทีมของคุณ กรุณากดปุ่มด้านล่างเพื่อเริ่มดำเนินการสืบสภาพและวิเคราะห์ปัญหา</p>
                                <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="start_preventive_measure">
                                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-teal-600 border border-transparent rounded-md font-bold text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition-colors">
                                        ▶️ เริ่มดำเนินการสืบสภาพ (Start Task 2)
                                    </button>
                                </form>
                            </div>
                        @else
                        <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="preventive_action">
                            
                            <fieldset @if($ticket->preventive_measure === 'done' || str_contains(Auth::user()->role, 'manager')) disabled @endif>
                            <div class="space-y-6">
                                <!-- 1. วิเคราะห์หาสาเหตุรากเหง้า -->
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <h4 class="font-bold text-gray-800 text-md mb-3 flex items-center">
                                        <span class="bg-teal-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs mr-2">1</span>
                                        การวิเคราะห์หาสาเหตุ (Root Cause Analysis)
                                    </h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-3">
                                            <label class="w-16 font-bold text-gray-700 text-sm shrink-0">ทำไม 1 :</label>
                                            <input type="text" name="why_1" class="flex-1 border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm" value="{{ $ticket->why_1 }}" />
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <label class="w-16 font-bold text-gray-700 text-sm shrink-0">ทำไม 2 :</label>
                                            <input type="text" name="why_2" class="flex-1 border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm" value="{{ $ticket->why_2 }}" />
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <label class="w-16 font-bold text-gray-700 text-sm shrink-0">ทำไม 3 :</label>
                                            <input type="text" name="why_3" class="flex-1 border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm" value="{{ $ticket->why_3 }}" />
                                        </div>
                                        <div class="flex items-start gap-3 mt-4 pt-3 border-t border-gray-200">
                                            <label class="w-32 font-bold text-gray-800 text-sm mt-2 shrink-0">สาเหตุรากเหง้า :</label>
                                            <textarea name="root_cause_detail" rows="2" class="flex-1 border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm">{{ $ticket->root_cause_detail }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 2. ดำเนินการแก้ไขปัญหาเฉพาะหน้า -->
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <h4 class="font-bold text-gray-800 text-md mb-3 flex items-center">
                                        <span class="bg-teal-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs mr-2">2</span>
                                        ดำเนินการแก้ไขปัญหาเฉพาะหน้า
                                    </h4>
                                    <div>
                                        <textarea name="resolution_notes" rows="2" class="block w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm" placeholder="ระบุการแก้ไขปัญหาเฉพาะหน้า...">{{ $ticket->resolution_notes }}</textarea>
                                    </div>
                                </div>
                                
                                <!-- 3. ดำเนินการป้องกัน -->
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <h4 class="font-bold text-gray-800 text-md mb-4 flex items-center">
                                        <span class="bg-teal-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs mr-2">3</span>
                                        ดำเนินการป้องกัน (Preventive Action)
                                    </h4>
                                    
                                    <div class="space-y-4 ml-2">
                                        <!-- 3.1 มาตรการป้องกันเฉพาะกรณี -->
                                        <div x-data="{ 
                                            measures: {{ empty($ticket->preventive_measure_specific) ? json_encode([['detail' => '', 'due_date' => '']]) : json_encode($ticket->preventive_measure_specific) }}
                                        }">
                                            <div class="flex items-center justify-between mb-2">
                                                <label class="font-bold text-gray-700 text-sm">3.1 มาตรการป้องกัน เฉพาะกรณี (Specific Preventive Measure)</label>
                                                @if($ticket->preventive_measure !== 'done' && !str_contains(Auth::user()->role, 'manager'))
                                                    <button type="button" @click="measures.push({detail: '', due_date: ''})" class="text-xs bg-teal-50 text-teal-600 border border-teal-200 hover:bg-teal-100 rounded px-2 py-1 transition">+ เพิ่มข้อ</button>
                                                @endif
                                            </div>
                                            <template x-for="(measure, index) in measures" :key="index">
                                                <div class="mb-3 p-3 bg-white border border-gray-200 rounded relative shadow-sm">
                                                    <textarea x-bind:name="`preventive_measure_specific[${index}][detail]`" x-model="measure.detail" rows="2" class="block w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm mb-2 text-sm" placeholder="ระบุวิธีป้องกันสำหรับเคสนี้โดยเฉพาะ..."></textarea>
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <label class="text-xs text-gray-600">วันกำหนดเสร็จ:</label>
                                                            @if($ticket->preventive_measure === 'done' || str_contains(Auth::user()->role, 'manager'))
                                                                <span class="text-sm font-medium text-gray-800" x-text="measure.due_date ? new Intl.DateTimeFormat('th-TH', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(measure.due_date)) : '-'"></span>
                                                            @else
                                                                <input type="date" x-bind:name="`preventive_measure_specific[${index}][due_date]`" x-model="measure.due_date" class="border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm text-xs p-1">
                                                            @endif
                                                        </div>
                                                        @if($ticket->preventive_measure !== 'done' && !str_contains(Auth::user()->role, 'manager'))
                                                            <button type="button" @click="measures.splice(index, 1)" x-show="measures.length > 1" class="text-xs text-red-500 hover:text-red-700 hover:underline">ลบออก</button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <!-- 3.2 มาตรการป้องกันทั้งระบบ -->
                                        <div x-data="{ 
                                            measures: {{ empty($ticket->preventive_measure_systemic) ? json_encode([['detail' => '', 'due_date' => '']]) : json_encode($ticket->preventive_measure_systemic) }}
                                        }">
                                            <div class="flex items-center justify-between mb-2">
                                                <label class="font-bold text-gray-700 text-sm">3.2 มาตรการป้องกัน ทั้งระบบ (Systemic Preventive Measure)</label>
                                                @if($ticket->preventive_measure !== 'done' && !str_contains(Auth::user()->role, 'manager'))
                                                    <button type="button" @click="measures.push({detail: '', due_date: ''})" class="text-xs bg-teal-50 text-teal-600 border border-teal-200 hover:bg-teal-100 rounded px-2 py-1 transition">+ เพิ่มข้อ</button>
                                                @endif
                                            </div>
                                            <template x-for="(measure, index) in measures" :key="index">
                                                <div class="mb-3 p-3 bg-white border border-gray-200 rounded relative shadow-sm">
                                                    <textarea x-bind:name="`preventive_measure_systemic[${index}][detail]`" x-model="measure.detail" rows="2" class="block w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm mb-2 text-sm" placeholder="ระบุวิธีป้องกันเพื่อไม่ให้ปัญหานี้เกิดซ้ำกับส่วนอื่นๆ ในระบบ..."></textarea>
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <label class="text-xs text-gray-600">วันกำหนดเสร็จ:</label>
                                                            @if($ticket->preventive_measure === 'done' || str_contains(Auth::user()->role, 'manager'))
                                                                <span class="text-sm font-medium text-gray-800" x-text="measure.due_date ? new Intl.DateTimeFormat('th-TH', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(measure.due_date)) : '-'"></span>
                                                            @else
                                                                <input type="date" x-bind:name="`preventive_measure_systemic[${index}][due_date]`" x-model="measure.due_date" class="border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm text-xs p-1">
                                                            @endif
                                                        </div>
                                                        @if($ticket->preventive_measure !== 'done')
                                                            <button type="button" @click="measures.splice(index, 1)" x-show="measures.length > 1" class="text-xs text-red-500 hover:text-red-700 hover:underline">ลบออก</button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <!-- 3.3 สรุปปัญหา/สาเหตุเกิดจาก (P-CAR) -->
                                        <div>
                                            <label class="block font-bold text-gray-700 text-sm mb-1">3.3 สรุปปัญหา/สาเหตุเกิดจาก (P-CAR Category)</label>
                                            <div class="overflow-x-auto border border-gray-300 rounded-md" x-data="{ selectedCause: '{{ $ticket->root_cause_category }}', mainCat: '{{ explode(' - ', $ticket->root_cause_category ?? '')[0] }}' }">
                                                <input type="hidden" name="root_cause_category" x-model="selectedCause">
                                                <table class="w-full text-sm text-left border-collapse">
                                                    <thead>
                                                        <tr class="bg-gray-50 border-b border-gray-300">
                                                            <th class="py-2 px-3 text-center border-r border-gray-300 w-1/5"><label class="inline-flex items-center whitespace-nowrap cursor-pointer"><input type="radio" value="คน" class="rounded text-teal-600 focus:ring-teal-500 mr-2" x-model="mainCat" @click="selectedCause = 'คน'"> คน</label></th>
                                                            <th class="py-2 px-3 text-center border-r border-gray-300 w-1/5"><label class="inline-flex items-center whitespace-nowrap cursor-pointer"><input type="radio" value="เครื่องจักร" class="rounded text-teal-600 focus:ring-teal-500 mr-2" x-model="mainCat" @click="selectedCause = 'เครื่องจักร'"> เครื่องจักร</label></th>
                                                            <th class="py-2 px-3 text-center border-r border-gray-300 w-1/5"><label class="inline-flex items-center whitespace-nowrap cursor-pointer"><input type="radio" value="วัสดุ" class="rounded text-teal-600 focus:ring-teal-500 mr-2" x-model="mainCat" @click="selectedCause = 'วัสดุ'"> วัสดุ</label></th>
                                                            <th class="py-2 px-3 text-center border-r border-gray-300 w-1/5"><label class="inline-flex items-center whitespace-nowrap cursor-pointer"><input type="radio" value="วิธีการ" class="rounded text-teal-600 focus:ring-teal-500 mr-2" x-model="mainCat" @click="selectedCause = 'วิธีการ'"> วิธีการ</label></th>
                                                            <th class="py-2 px-3 text-center w-1/5"><label class="inline-flex items-center whitespace-nowrap cursor-pointer"><input type="radio" value="สิ่งแวดล้อม" class="rounded text-teal-600 focus:ring-teal-500 mr-2" x-model="mainCat" @click="selectedCause = 'สิ่งแวดล้อม'"> สิ่งแวดล้อม</label></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="align-top">
                                                        <tr>
                                                            <td class="p-3 border-r border-gray-300">
                                                                <div class="space-y-3">
                                                                    <label class="flex items-start text-xs text-gray-700 cursor-pointer hover:text-teal-700"><input type="radio" value="คน - ไม่รู้มาตรฐาน" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 mr-2" x-model="selectedCause" @click="mainCat = 'คน'"> <span class="leading-tight">ไม่รู้มาตรฐาน</span></label>
                                                                    <label class="flex items-start text-xs text-gray-700 cursor-pointer hover:text-teal-700"><input type="radio" value="คน - ไม่ทำตามมาตรฐาน" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 mr-2" x-model="selectedCause" @click="mainCat = 'คน'"> <span class="leading-tight">ไม่ทำตามมาตรฐาน</span></label>
                                                                    <label class="flex items-start text-xs text-gray-700 cursor-pointer hover:text-teal-700"><input type="radio" value="คน - ทำตามแล้วยังเกิด" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 mr-2" x-model="selectedCause" @click="mainCat = 'คน'"> <span class="leading-tight">ทำตามแล้วยังเกิด</span></label>
                                                                </div>
                                                            </td>
                                                            <td class="p-3 border-r border-gray-300">
                                                                <div class="space-y-3">
                                                                    <label class="flex items-start text-xs text-gray-700 cursor-pointer hover:text-teal-700"><input type="radio" value="เครื่องจักร - ออกแบบไม่ดี" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 mr-2" x-model="selectedCause" @click="mainCat = 'เครื่องจักร'"> <span class="leading-tight">ออกแบบไม่ดี</span></label>
                                                                    <label class="flex items-start text-xs text-gray-700 cursor-pointer hover:text-teal-700"><input type="radio" value="เครื่องจักร - ไม่ได้กำหนดมาตรฐาน" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 mr-2" x-model="selectedCause" @click="mainCat = 'เครื่องจักร'"> <span class="leading-tight">ไม่ได้กำหนดมาตรฐาน</span></label>
                                                                </div>
                                                            </td>
                                                            <td class="p-3 border-r border-gray-300">
                                                                <div class="space-y-3">
                                                                    <label class="flex items-start text-xs text-gray-700 cursor-pointer hover:text-teal-700"><input type="radio" value="วัสดุ - ไม่ได้กำหนดมาตรฐาน" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 mr-2" x-model="selectedCause" @click="mainCat = 'วัสดุ'"> <span class="leading-tight">ไม่ได้กำหนดมาตรฐาน</span></label>
                                                                    <label class="flex items-start text-xs text-gray-700 cursor-pointer hover:text-teal-700"><input type="radio" value="วัสดุ - กำหนดไม่เหมาะสม" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 mr-2" x-model="selectedCause" @click="mainCat = 'วัสดุ'"> <span class="leading-tight">กำหนดไม่เหมาะสม</span></label>
                                                                </div>
                                                            </td>
                                                            <td class="p-3 border-r border-gray-300">
                                                                <div class="space-y-3">
                                                                    <label class="flex items-start text-xs text-gray-700 cursor-pointer hover:text-teal-700"><input type="radio" value="วิธีการ - ไม่มีมาตรฐาน" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 mr-2" x-model="selectedCause" @click="mainCat = 'วิธีการ'"> <span class="leading-tight">ไม่มีมาตรฐาน</span></label>
                                                                    <label class="flex items-start text-xs text-gray-700 cursor-pointer hover:text-teal-700"><input type="radio" value="วิธีการ - กำหนดไม่เหมาะสม" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 mr-2" x-model="selectedCause" @click="mainCat = 'วิธีการ'"> <span class="leading-tight">กำหนดไม่เหมาะสม</span></label>
                                                                </div>
                                                            </td>
                                                            <td class="p-3">
                                                                <div class="space-y-3">
                                                                    <label class="flex items-start text-xs text-gray-700 cursor-pointer hover:text-teal-700"><input type="radio" value="สิ่งแวดล้อม - ไม่มีมาตรฐาน" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 mr-2" x-model="selectedCause" @click="mainCat = 'สิ่งแวดล้อม'"> <span class="leading-tight">ไม่มีมาตรฐาน</span></label>
                                                                    <label class="flex items-start text-xs text-gray-700 cursor-pointer hover:text-teal-700"><input type="radio" value="สิ่งแวดล้อม - กำหนดไม่เหมาะสม" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 mr-2" x-model="selectedCause" @click="mainCat = 'สิ่งแวดล้อม'"> <span class="leading-tight">กำหนดไม่เหมาะสม</span></label>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </fieldset>
                            
                            @if($ticket->preventive_measure !== 'done' && !str_contains(Auth::user()->role, 'manager'))
                            <div class="mt-6">
                                <button type="submit" class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-md shadow-sm text-base font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    บันทึกข้อมูล Task 2
                                </button>
                            </div>
                            @endif
                        </form>
                        @endif
                    </div>
                    @endif

                    <!-- Action Form for Manager to Close Preventive Measure -->
                    @if($ticket->preventive_measure === 'pending_review' && str_contains(Auth::user()->role, 'manager'))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-blue-500 mb-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            ปิดมาตรการป้องกันปัญหา (Close Preventive Action)
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">ช่างเทคนิคได้บันทึกข้อมูลการป้องกันปัญหา (Task 2) แล้ว กรุณาตรวจสอบข้อมูล และกดปุ่มด้านล่างเพื่อปิดกระบวนการ</p>
                        
                        <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="close_preventive_measure">
                            
                            <button type="submit" class="w-full flex justify-center items-center gap-2 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                ✅ ยืนยันตรวจสอบและปิดมาตรการป้องกัน
                            </button>
                        </form>
                    </div>
                    @endif

                    <!-- ข้อมูลผู้แจ้ง -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">ข้อมูลผู้แจ้ง (Requester)</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="block text-sm text-gray-500">ชื่อ-นามสกุล</span>
                                <span class="block text-md text-gray-900 font-medium">{{ $ticket->requester_name }}</span>
                            </div>
                            <div>
                                <span class="block text-sm text-gray-500">แผนก (Department)</span>
                                <span class="block text-md text-gray-900 font-medium">{{ $ticket->department ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-sm text-gray-500">อีเมล (Email)</span>
                                <span class="block text-md text-gray-900 font-medium">{{ $ticket->requester_email }}</span>
                            </div>
                            <div>
                                <span class="block text-sm text-gray-500">เบอร์โทรติดต่อ (Phone)</span>
                                <span class="block text-md text-gray-900 font-medium">{{ $ticket->requester_phone ?? '-' }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="block text-sm text-gray-500">สถานที่ (Location)</span>
                                <span class="block text-md text-gray-900 font-medium">{{ $ticket->location ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- CSAT Feedback Display -->
                    @if($ticket->rating)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-yellow-400">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            ผลการประเมินความพึงพอใจ (CSAT)
                        </h3>
                        <div class="mb-4">
                            <span class="block text-sm text-gray-500 mb-2">คะแนนความพึงพอใจ</span>
                            <div class="flex items-center gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-6 h-6 {{ $i <= $ticket->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @endfor
                                <span class="ml-2 font-bold text-gray-700">{{ $ticket->rating }} / 5</span>
                            </div>
                        </div>
                        @if($ticket->feedback)
                        <div>
                            <span class="block text-sm text-gray-500 mb-1">ข้อเสนอแนะเพิ่มเติม</span>
                            <div class="bg-gray-50 p-4 rounded-md border border-gray-200 text-sm text-gray-800 italic">
                                "{{ $ticket->feedback }}"
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Action Form for Cancel Ticket -->
                    @php
                        $isManager = str_contains(Auth::user()->role, 'manager');
                        $canCancel = ($isRequester || $isHelpdesk || $isManager) && in_array($ticket->status, ['pending', 'assigned', 'analyzing', 'in_progress']);
                    @endphp
                    @if($canCancel)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-red-500" x-data="{ showCancelConfirm: false }">
                        <h3 class="text-lg font-bold text-red-600 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            ยกเลิกใบงาน (Cancel Ticket)
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">หากใบงานนี้ไม่ถูกต้อง หรือไม่ต้องการดำเนินการต่อ สามารถกดยกเลิกได้</p>
                        
                        <button type="button" @click="showCancelConfirm = true" class="w-full flex justify-center py-2 px-4 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                            ❌ ยกเลิกใบงาน (Cancel)
                        </button>

                        <!-- Cancel Modal -->
                        <div x-show="showCancelConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
                            <div @click.away="showCancelConfirm = false" class="bg-white rounded-lg p-6 w-full max-w-md mx-4 shadow-xl">
                                <h4 class="text-lg font-bold text-gray-900 mb-4">ยืนยันการยกเลิกใบงาน</h4>
                                
                                <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="cancel">
                                    
                                    <div class="mb-4">
                                        <label for="cancellation_reason" class="block font-medium text-sm text-gray-700 mb-1">เหตุผลการยกเลิก <span class="text-red-500">*</span></label>
                                        <textarea id="cancellation_reason" name="cancellation_reason" rows="3" required class="block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm" placeholder="ระบุเหตุผลสั้นๆ เช่น แจ้งซ้ำ, ผู้ใช้แก้ปัญหาได้เองแล้ว"></textarea>
                                    </div>
                                    
                                    @if(in_array($ticket->status, ['analyzing', 'in_progress']))
                                    <div class="mb-4 p-3 bg-amber-50 border-l-4 border-amber-400 text-amber-700 text-sm">
                                        <strong>⚠️ คำเตือน:</strong> เคสนี้มีช่างกำลังดำเนินการอยู่ การยกเลิกจะทำให้การปฏิบัติงานถูกระงับทันที
                                    </div>
                                    @endif
                                    
                                    <div class="flex justify-end gap-3 mt-6">
                                        <button type="button" @click="showCancelConfirm = false" class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            กลับ
                                        </button>
                                        <button type="submit" class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            ยืนยันยกเลิก
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                    <!-- ระบบโต้ตอบ / Comments -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t border-gray-200">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                            พูดคุย / สอบถามข้อมูลเพิ่มเติม
                        </h3>
                        
                        <div id="comments-list" class="space-y-4 mb-6 max-h-96 overflow-y-auto pr-2">
                            @forelse($ticket->comments as $comment)
                                <div class="flex {{ $comment->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                                    <div class="{{ $comment->user_id === Auth::id() ? 'bg-indigo-50 border-indigo-100 text-indigo-900' : 'bg-gray-50 border-gray-200 text-gray-800' }} border p-3 rounded-lg max-w-[80%]">
                                        <div class="flex justify-between items-center mb-1 gap-4">
                                            <span class="font-bold text-xs">
                                                @if(Auth::user()->role === 'user' && $comment->user->role !== 'user')
                                                    เจ้าหน้าที่ไอที (IT Support)
                                                @else
                                                    {{ $comment->user->name }} ({{ $comment->user->role }})
                                                @endif
                                            </span>
                                            <span class="text-[10px] text-gray-500">
                                                <span class="hidden sm:inline">{{ $comment->created_at->translatedFormat('d F Y H:i') }}</span>
                                                <span class="sm:hidden">{{ $comment->created_at->translatedFormat('d M y H:i') }}</span>
                                            </span>
                                        </div>
                                        <p class="text-sm whitespace-pre-wrap">{{ $comment->message }}</p>
                                        @if($comment->attachment_path)
                                            <div class="mt-2">
                                                <a href="{{ asset('storage/' . $comment->attachment_path) }}" target="_blank" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                    ดูไฟล์แนบ
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-gray-500 text-sm italic border-2 border-dashed border-gray-200 rounded-md">
                                    ยังไม่มีข้อความพูดคุย
                                </div>
                            @endforelse
                        </div>

                        <!-- Form for new comment -->
                        @if(!in_array($ticket->status, ['closed', 'cancelled']))
                        <form action="{{ route('tickets.comments.store', $ticket->id) }}" method="POST" enctype="multipart/form-data" class="border-t pt-4">
                            @csrf
                            <div class="mb-3">
                                <label for="message" class="sr-only">ข้อความ</label>
                                <textarea name="message" id="message" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="พิมพ์ข้อความตอบกลับที่นี่..."></textarea>
                                <x-input-error :messages="$errors->get('message')" class="mt-2" />
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <label for="attachment" class="cursor-pointer text-sm text-gray-600 hover:text-indigo-600 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                        <span id="file-name-{{ $ticket->id }}">แนบไฟล์รูปภาพ (ไม่เกิน 5MB)</span>
                                    </label>
                                    <input type="file" name="attachment" id="attachment" class="hidden" accept="image/*" capture="environment" onchange="if(this.files[0] && this.files[0].size > 5242880) { alert('ขนาดไฟล์เกิน 5MB'); this.value=''; document.getElementById('file-name-{{ $ticket->id }}').textContent = 'แนบไฟล์รูปภาพ (ไม่เกิน 5MB)'; } else { document.getElementById('file-name-{{ $ticket->id }}').textContent = this.files[0] ? this.files[0].name : 'แนบไฟล์รูปภาพ (ไม่เกิน 5MB)'; }">
                                    <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
                                </div>
                                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                    ส่งข้อความ
                                </button>
                            </div>
                        </form>
                        @endif
                    </div>
                </div>

                <!-- ฝั่งขวา: Triage & Assignment Form -->
                <div class="space-y-6">
                    @php
                        $canTriage = false;
                        if (in_array(Auth::user()->role, ['helpdesk', 'manager'])) {
                            if (in_array($ticket->status, ['pending', 'assigned'])) {
                                $canTriage = true;
                            } elseif (is_null($ticket->escalated_to_team) && !in_array($ticket->status, ['resolved', 'approved', 'closed', 'cancelled'])) {
                                $canTriage = true;
                            }
                        }
                    @endphp

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-indigo-500">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            {{ $canTriage ? 'ประเมินและจ่ายงาน (Triage)' : 'ข้อมูลการมอบหมาย (Assignment)' }}
                        </h3>

                        @if($canTriage)
                            <form action="{{ route('tickets.assign', $ticket->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                @php
                                    $currentPriority = old('priority') ?? ($ticket->status !== 'pending' ? $ticket->priority : null);
                                    $currentTeam = old('escalated_to_team') ?? ($ticket->status !== 'pending' ? ($ticket->escalated_to_team ?? 'None') : null);
                                @endphp

                                <div class="mb-4">
                                    <x-input-label for="priority" value="ระดับความเร่งด่วน (Priority)" class="font-bold text-gray-700" />
                                    <select name="priority" id="priority" required class="mt-1 block w-full rounded-md border-gray-300 border-l-4 border-l-indigo-500 bg-indigo-50/30 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="" disabled {{ is_null($currentPriority) ? 'selected' : '' }}>-- เลือกระดับความเร่งด่วน --</option>
                                        <option value="low" {{ $currentPriority === 'low' ? 'selected' : '' }}>ทั่วไป/ต่ำ (Low)</option>
                                        <option value="medium" {{ $currentPriority === 'medium' ? 'selected' : '' }}>ปานกลาง (Medium)</option>
                                        <option value="high" {{ $currentPriority === 'high' ? 'selected' : '' }}>สูง (High)</option>
                                        <option value="urgent" {{ $currentPriority === 'urgent' ? 'selected' : '' }}>ด่วนที่สุด (Urgent)</option>
                                    </select>
                                </div>

                                <div class="mb-6">
                                    <x-input-label for="escalated_to_team" value="ส่งต่อให้ทีม (Escalate To)" class="font-bold text-gray-700" />
                                    <select name="escalated_to_team" id="escalated_to_team" required class="mt-1 block w-full rounded-md border-gray-300 border-l-4 border-l-indigo-500 bg-indigo-50/30 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="" disabled {{ is_null($currentTeam) ? 'selected' : '' }}>-- เลือกการดำเนินการ --</option>
                                        <option value="None" {{ $currentTeam === 'None' ? 'selected' : '' }}>ไม่ส่งต่อ (Helpdesk จัดการเอง)</option>
                                        <option value="Hardware" {{ $currentTeam === 'Hardware' ? 'selected' : '' }}>ทีม Hardware</option>
                                        <option value="Network" {{ $currentTeam === 'Network' ? 'selected' : '' }}>ทีม Network</option>
                                        <option value="Software" {{ $currentTeam === 'Software' ? 'selected' : '' }}>ทีม Software</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-2">* เมื่อกดส่งต่อ สถานะจะเปลี่ยนเป็น "กำลังวิเคราะห์" โดยอัตโนมัติ</p>
                                </div>

                                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                    บันทึกการประเมิน
                                </button>
                            </form>
                        @else
                            <!-- View only for others -->
                            <div class="space-y-4">
                                <div>
                                    <span class="block text-sm text-gray-500">สถานะใบงาน (Status)</span>
                                    <span class="block text-md font-bold text-indigo-700">
                                        {{ match($ticket->status) {
                                            'pending' => 'เปิดเคส / รอดำเนินการ',
                                            'assigned' => 'รับเรื่อง / รอสืบสภาพ',
                                            'analyzing' => 'กำลังสืบสภาพ / วิเคราะห์สาเหตุ',
                                            'in_progress' => 'กำลังดำเนินการแก้ไข',
                                            'resolved' => 'รอผู้แจ้งรับงาน',
                                            'approved' => 'ผู้แจ้งรับงานแล้ว (รอหัวหน้าปิดใบงาน)',
                                            'closed' => 'ปิดใบงาน',
                                            'cancelled' => 'ยกเลิก',
                                            default => 'ไม่ระบุ'
                                        } }}
                                    </span>
                                </div>
                                <div>
                                    <span class="block text-sm text-gray-500">ระดับความเร่งด่วน (Priority)</span>
                                    <span class="block text-md font-bold {{ $ticket->priority == 'urgent' ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ $ticket->priority_label }}
                                    </span>
                                </div>
                                <div>
                                    <span class="block text-sm text-gray-500">ทีมที่รับผิดชอบ (Assigned Team)</span>
                                    <span class="block text-md font-bold text-gray-900">
                                        {{ $ticket->assigned_team_label }}
                                    </span>
                                </div>
                            </div>
                        @endif

                    </div>

                    <!-- ไทม์ไลน์สถานะเคส (Timeline) -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            ไทม์ไลน์สถานะ (Timeline)
                        </h3>
                        
                        <div class="relative pl-4 space-y-5">
                            <!-- Vertical Line -->
                            <div class="absolute top-2 bottom-2 left-5 w-0.5 bg-gray-200"></div>
                            
                            @php
                                $isCancelled = $ticket->status === 'cancelled';
                            @endphp
                            
                            <!-- เปิดเคส -->
                            <div class="relative flex items-start gap-3 {{ $isCancelled ? 'opacity-40' : '' }}">
                                <div class="relative flex items-center justify-center w-2.5 h-2.5 mt-1.5 z-10 ring-4 ring-white rounded-full {{ $isCancelled ? 'bg-gray-400' : 'bg-blue-500' }}">
                                    @if(in_array($ticket->status, ['pending', 'assigned']))
                                        <span class="absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75 animate-ping"></span>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">เปิดเคส / รอดำเนินการ</p>
                                    @if(Auth::user()->role !== 'user')
                                        <p class="text-xs text-gray-500">{{ $ticket->created_at->translatedFormat('d F Y H:i น.') }}</p>
                                        <p class="text-xs text-indigo-600 font-medium mt-1">โดย: {{ $ticket->requester_name }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- กำลังสืบสภาพ -->
                            <div class="relative flex items-start gap-3 {{ (!$ticket->analyzing_at || $isCancelled) ? 'opacity-40' : '' }}">
                                <div class="relative flex items-center justify-center w-2.5 h-2.5 mt-1.5 z-10 ring-4 ring-white rounded-full {{ ($ticket->analyzing_at && !$isCancelled) ? 'bg-purple-500' : 'bg-gray-300' }}">
                                    @if($ticket->status === 'analyzing')
                                        <span class="absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75 animate-ping"></span>
                                    @endif
                                </div>
                                <div class="w-full">
                                    <p class="text-sm font-semibold text-gray-800">สืบสภาพ และวิเคราะห์หาสาเหตุ</p>
                                    @if($ticket->analyzing_at)
                                        @if(Auth::user()->role !== 'user')
                                            <div class="mt-1.5 bg-gray-50 rounded p-2 text-xs border border-gray-200 inline-block min-w-[200px]">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500">เริ่ม:</span>
                                                    <span class="text-gray-700 font-medium">{{ $ticket->analyzing_at->translatedFormat('d F Y H:i น.') }}</span>
                                                </div>
                                                @if($ticket->in_progress_at)
                                                <div class="flex justify-between mt-1">
                                                    <span class="text-gray-500">จบ:</span>
                                                    <span class="text-gray-700 font-medium">{{ $ticket->in_progress_at->translatedFormat('d F Y H:i น.') }}</span>
                                                </div>
                                                @endif
                                            </div>
                                            @if($ticket->analyzingBy)
                                                <p class="text-xs text-indigo-600 font-medium mt-1">บันทึกโดย: {{ $ticket->analyzingBy->name }}</p>
                                            @endif
                                        @endif
                                    @else
                                        <p class="text-xs text-gray-400 mt-1">รอการดำเนินการ</p>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- ดำเนินการแก้ไข และส่งมอบงาน -->
                            <div class="relative flex items-start gap-3 {{ (!$ticket->in_progress_at || $isCancelled) ? 'opacity-40' : '' }}">
                                <div class="relative flex items-center justify-center w-2.5 h-2.5 mt-1.5 z-10 ring-4 ring-white rounded-full {{ ($ticket->in_progress_at && !$isCancelled) ? 'bg-indigo-500' : 'bg-gray-300' }}">
                                    @if($ticket->status === 'in_progress')
                                        <span class="absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75 animate-ping"></span>
                                    @endif
                                </div>
                                <div class="w-full">
                                    <p class="text-sm font-semibold text-gray-800">ดำเนินการแก้ไข และส่งมอบงาน</p>
                                    @if($ticket->in_progress_at)
                                        @if(Auth::user()->role !== 'user')
                                            <div class="mt-1.5 bg-gray-50 rounded p-2 text-xs border border-gray-200 inline-block min-w-[200px]">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500">เริ่ม:</span>
                                                    <span class="text-gray-700 font-medium">{{ $ticket->in_progress_at->translatedFormat('d F Y H:i น.') }}</span>
                                                </div>
                                                @if($ticket->resolved_at)
                                                <div class="flex justify-between mt-1 pt-1 border-t border-gray-200">
                                                    <span class="text-emerald-600 font-semibold">ส่งมอบเมื่อ:</span>
                                                    <span class="text-emerald-700 font-bold">{{ $ticket->resolved_at->translatedFormat('d F Y H:i น.') }}</span>
                                                </div>
                                                @endif
                                            </div>
                                            @if($ticket->resolvedBy)
                                                <p class="text-xs text-emerald-600 font-medium mt-1">ส่งงานโดย: {{ $ticket->resolvedBy->name }}</p>
                                            @elseif($ticket->inProgressBy)
                                                <p class="text-xs text-indigo-600 font-medium mt-1">ดำเนินการโดย: {{ $ticket->inProgressBy->name }}</p>
                                            @endif
                                        @endif
                                    @else
                                        <p class="text-xs text-gray-400 mt-1">รอการดำเนินการ</p>
                                    @endif
                                </div>
                            </div>

                            <!-- รับงาน (รอผู้แจ้งรับงาน / Approved) -->
                            <div class="relative flex items-start gap-3 {{ (!$ticket->resolved_at || $isCancelled) ? 'opacity-40' : '' }}">
                                <div class="relative flex items-center justify-center w-2.5 h-2.5 mt-1.5 z-10 ring-4 ring-white rounded-full {{ ($ticket->resolved_at && !$isCancelled) ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                    @if($ticket->status === 'resolved')
                                        <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ $ticket->approved_at ? 'ผู้แจ้งรับทราบการแก้ไข' : 'รอผู้แจ้งรับงาน' }}</p>
                                    @if($ticket->approved_at)
                                        @if(Auth::user()->role !== 'user')
                                            <p class="text-xs text-gray-500 mt-1">{{ $ticket->approved_at->translatedFormat('d F Y H:i น.') }}</p>
                                            @if($ticket->approvedBy)
                                                <p class="text-xs text-emerald-600 font-medium mt-1">ผู้รับงาน: {{ $ticket->approvedBy->name }}</p>
                                            @endif
                                        @endif
                                    @else
                                        <p class="text-xs text-gray-400 mt-1">รอการดำเนินการ</p>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- ปิดใบงาน (รอหัวหน้าปิดใบงาน / Closed) -->
                            <div class="relative flex items-start gap-3 {{ (!$ticket->approved_at || $isCancelled) ? 'opacity-40' : '' }}">
                                <div class="relative flex items-center justify-center w-2.5 h-2.5 mt-1.5 z-10 ring-4 ring-white rounded-full {{ ($ticket->approved_at && !$isCancelled) ? 'bg-amber-500' : 'bg-gray-300' }}">
                                    @if($ticket->status === 'approved')
                                        <span class="absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75 animate-ping"></span>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ $ticket->closed_at ? 'ปิดใบงาน' : 'รอปิดใบงาน' }}</p>
                                    @if($ticket->closed_at)
                                        @if(Auth::user()->role !== 'user')
                                            <p class="text-xs text-gray-500">{{ $ticket->closed_at->translatedFormat('d F Y H:i น.') }}</p>
                                            @if($ticket->closedBy)
                                                <p class="text-xs text-gray-800 font-medium mt-1">ปิดโดย: {{ $ticket->closedBy->name }}</p>
                                            @endif
                                        @endif
                                    @else
                                        <p class="text-xs text-gray-400">รอการดำเนินการ</p>
                                    @endif
                                </div>
                            </div>
                            

                            
                            @if($ticket->status === 'cancelled')
                            <!-- ยกเลิกใบงาน (Cancelled) -->
                            <div class="relative flex items-start gap-3">
                                <div class="relative flex items-center justify-center w-2.5 h-2.5 mt-1.5 z-10 ring-4 ring-white rounded-full bg-red-500">
                                    <span class="absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75 animate-ping"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-red-600">ยกเลิกใบงานแล้ว</p>
                                    @if($ticket->cancelled_at)
                                        @if(Auth::user()->role !== 'user')
                                            <p class="text-xs text-gray-500">{{ $ticket->cancelled_at->translatedFormat('d F Y H:i น.') }}</p>
                                            @if($ticket->cancelledBy)
                                                <p class="text-xs text-red-600 font-medium mt-1">ยกเลิกโดย: {{ $ticket->cancelledBy->name }}</p>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- สรุปเวลาการทำงาน Task 1 (Case Summary) -->
                    @if(Auth::user()->role !== 'user')
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6 border-t-4 border-indigo-500">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            สรุปเวลาการทำงาน Task 1
                        </h3>
                        
                        <div class="space-y-4 text-sm">
                            <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                                <span class="text-gray-500">แจ้งเมื่อ:</span>
                                <span class="font-semibold text-gray-800">{{ $ticket->created_at->translatedFormat('d F Y H:i น.') }}</span>
                            </div>
                            
                            @if(in_array($ticket->status, ['closed', 'cancelled']))
                                @php
                                    $end_date = $ticket->status === 'closed' ? ($ticket->closed_at ?? $ticket->updated_at) : ($ticket->cancelled_at ?? $ticket->updated_at);
                                    $end_carbon = \Carbon\Carbon::parse($end_date);
                                    $diff = $ticket->created_at->copy()->startOfMinute()->diff($end_carbon->copy()->startOfMinute());
                                    $totalTimeStr = '';
                                    if ($diff->d > 0) $totalTimeStr .= $diff->d . ' วัน ';
                                    if ($diff->h > 0) $totalTimeStr .= $diff->h . ' ชั่วโมง ';
                                    if ($diff->i > 0) $totalTimeStr .= $diff->i . ' นาที';
                                    if ($totalTimeStr === '') $totalTimeStr = 'น้อยกว่า 1 นาที';
                                @endphp
                                <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                                    <span class="text-gray-500">{{ $ticket->status === 'closed' ? 'ปิดใบงานเมื่อ:' : 'ยกเลิกใบงานเมื่อ:' }}</span>
                                    <span class="font-semibold text-gray-800">{{ $end_carbon->translatedFormat('d F Y H:i น.') }}</span>
                                </div>
                                @if(Auth::user()->role !== 'user')
                                <div class="flex justify-between items-center bg-emerald-50 p-3 rounded-md border border-emerald-100 mt-2">
                                    <span class="text-emerald-700 font-bold">เวลารวมทั้งหมด:</span>
                                    <span class="text-emerald-700 font-black text-base">{{ $totalTimeStr }}</span>
                                </div>
                                @endif
                            @else
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500">สถานะปัจจุบัน:</span>
                                    <span class="font-semibold {{ match($ticket->status) {
                                        'pending', 'assigned' => 'text-blue-600',
                                        'analyzing' => 'text-purple-600',
                                        'in_progress' => 'text-indigo-600',
                                        'resolved' => 'text-emerald-600',
                                        'approved' => 'text-amber-600',
                                        default => 'text-gray-600'
                                    } }}">
                                        {{ match($ticket->status) {
                                            'pending' => 'เปิดเคส / รอดำเนินการ',
                                            'assigned' => 'รับเรื่อง / รอสืบสภาพ',
                                            'analyzing' => 'กำลังสืบสภาพ / วิเคราะห์สาเหตุ',
                                            'in_progress' => 'กำลังดำเนินการแก้ไข',
                                            'resolved' => 'รอผู้แจ้งรับงาน',
                                            'approved' => 'ผู้แจ้งรับงานแล้ว (รอหัวหน้าปิดใบงาน)',
                                            default => 'กำลังดำเนินการ'
                                        } }}
                                        @if(Auth::user()->role !== 'user')
                                            (ผ่านไปแล้ว {{ $ticket->created_at->diffForHumans(null, true) }})
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    @if($ticket->requires_preventive_measure && Auth::user()->role !== 'user')
                    <!-- ไทม์ไลน์ Task 2 (Preventive Action) -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6 border-t-4 border-teal-500">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            ไทม์ไลน์สถานะ Task 2
                        </h3>
                        
                        <div class="relative pl-4 space-y-5">
                            <!-- Vertical Line -->
                            <div class="absolute top-2 bottom-2 left-5 w-0.5 bg-gray-200"></div>
                            
                            @php
                                $isCancelled = $ticket->status === 'cancelled';
                            @endphp

                            <!-- Task 2: เปิด P-CAR -->
                            <div class="relative flex items-start gap-3 {{ (!$ticket->pcar_opened_at || $isCancelled) ? 'opacity-40' : '' }}">
                                <div class="relative flex items-center justify-center w-2.5 h-2.5 mt-1.5 z-10 ring-4 ring-white rounded-full {{ ($ticket->pcar_opened_at && !$isCancelled) ? 'bg-indigo-500' : 'bg-gray-300' }}">
                                </div>
                                <div class="w-full">
                                    <p class="text-sm font-semibold text-gray-800">1. เปิด P-CAR</p>
                                    @if($ticket->pcar_opened_at)
                                        <p class="text-xs text-gray-500 mt-1">{{ $ticket->pcar_opened_at->translatedFormat('d F Y H:i น.') }}</p>
                                        @if($ticket->pcarOpenedBy)
                                            <p class="text-xs text-indigo-600 font-medium mt-1">อนุมัติโดย: {{ $ticket->pcarOpenedBy->name }}</p>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <!-- Task 2: สืบสภาพและวิเคราะห์ P-CAR -->
                            <div class="relative flex items-start gap-3 {{ (!in_array($ticket->preventive_measure, ['in_progress', 'pending_review', 'done']) || $isCancelled) ? 'opacity-40' : '' }}">
                                <div class="relative flex items-center justify-center w-2.5 h-2.5 mt-1.5 z-10 ring-4 ring-white rounded-full {{ (in_array($ticket->preventive_measure, ['pending_review', 'done']) && !$isCancelled) ? 'bg-teal-500' : 'bg-gray-300' }}">
                                    @if($ticket->preventive_measure === 'in_progress')
                                        <span class="absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75 animate-ping"></span>
                                    @endif
                                </div>
                                <div class="w-full">
                                    <p class="text-sm font-semibold text-gray-800">2. หาสาเหตุรากเหง้า และสร้างมาตรการป้องกัน</p>
                                    @if(in_array($ticket->preventive_measure, ['in_progress', 'pending_review', 'done']))
                                        @if($ticket->pcar_opened_at)
                                            <p class="text-xs text-gray-500 mt-1">เริ่ม: {{ $ticket->pcar_opened_at->translatedFormat('d F Y H:i น.') }}</p>
                                        @endif
                                        @if($ticket->pcar_analyzed_at)
                                            <p class="text-xs text-gray-500 mt-0.5">สิ้นสุด: {{ $ticket->pcar_analyzed_at->translatedFormat('d F Y H:i น.') }}</p>
                                        @endif
                                        @if($ticket->pcarAnalyzedBy)
                                            <p class="text-xs text-teal-700 font-medium mt-1">บันทึกโดย: {{ $ticket->pcarAnalyzedBy->name }}</p>
                                        @endif
                                    @else
                                        <p class="text-xs text-gray-400 mt-1">รอการดำเนินการจากช่างเฉพาะทาง</p>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Task 2: ตรวจสอบและปิดมาตรการป้องกัน -->
                            <div class="relative flex items-start gap-3 {{ (!in_array($ticket->preventive_measure, ['pending_review', 'done']) || $isCancelled) ? 'opacity-40' : '' }}">
                                <div class="relative flex items-center justify-center w-2.5 h-2.5 mt-1.5 z-10 ring-4 ring-white rounded-full {{ ($ticket->preventive_measure === 'done' && !$isCancelled) ? 'bg-blue-500' : 'bg-gray-300' }}">
                                    @if($ticket->preventive_measure === 'pending_review')
                                        <span class="absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75 animate-ping"></span>
                                    @endif
                                </div>
                                <div class="w-full">
                                    <p class="text-sm font-semibold text-gray-800">3. ตรวจสอบและปิดมาตรการป้องกัน</p>
                                    @if($ticket->preventive_measure === 'done')
                                        @if($ticket->pcar_closed_at)
                                            <p class="text-xs text-gray-500 mt-1">{{ $ticket->pcar_closed_at->translatedFormat('d F Y H:i น.') }}</p>
                                        @endif
                                        @if($ticket->pcarClosedBy)
                                            <p class="text-xs text-blue-700 font-medium mt-1">ตรวจสอบโดย: {{ $ticket->pcarClosedBy->name }}</p>
                                        @endif
                                    @else
                                        <p class="text-xs text-gray-400 mt-1">รอหัวหน้าตรวจสอบ</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- สรุปเวลาการทำงาน Task 2 (Case Summary Task 2) -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6 border-t-4 border-teal-500">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            สรุปเวลาการทำงาน Task 2
                        </h3>
                        
                        <div class="space-y-4 text-sm">
                            <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                                <span class="text-gray-500">เริ่มเปิด P-CAR เมื่อ:</span>
                                <span class="font-semibold text-gray-800">{{ $ticket->pcar_opened_at ? $ticket->pcar_opened_at->translatedFormat('d F Y H:i น.') : '-' }}</span>
                            </div>
                            
                            @if($ticket->preventive_measure === 'done')
                                @php
                                    $end_carbon_t2 = \Carbon\Carbon::parse($ticket->pcar_closed_at ?? clone $ticket->updated_at);
                                    $diff_t2 = $ticket->pcar_opened_at ? $ticket->pcar_opened_at->copy()->startOfMinute()->diff($end_carbon_t2->copy()->startOfMinute()) : null;
                                    $totalTimeStrT2 = '';
                                    if ($diff_t2) {
                                        if ($diff_t2->d > 0) $totalTimeStrT2 .= $diff_t2->d . ' วัน ';
                                        if ($diff_t2->h > 0) $totalTimeStrT2 .= $diff_t2->h . ' ชั่วโมง ';
                                        if ($diff_t2->i > 0) $totalTimeStrT2 .= $diff_t2->i . ' นาที';
                                        if ($totalTimeStrT2 === '') $totalTimeStrT2 = 'น้อยกว่า 1 นาที';
                                    }
                                @endphp
                                <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                                    <span class="text-gray-500">ปิดมาตรการป้องกันเมื่อ:</span>
                                    <span class="font-semibold text-gray-800">{{ $ticket->pcar_closed_at ? $ticket->pcar_closed_at->translatedFormat('d F Y H:i น.') : '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center bg-teal-50 p-3 rounded-md border border-teal-100 mt-2">
                                    <span class="text-teal-700 font-bold">เวลารวม Task 2 ทั้งหมด:</span>
                                    <span class="text-teal-700 font-black text-base">{{ $totalTimeStrT2 ?: '-' }}</span>
                                </div>
                            @else
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500">สถานะปัจจุบัน (Task 2):</span>
                                    <span class="font-semibold {{ match($ticket->preventive_measure) {
                                        'in_progress' => 'text-teal-600',
                                        'pending_review' => 'text-blue-600',
                                        default => 'text-gray-600'
                                    } }}">
                                        {{ match($ticket->preventive_measure) {
                                            'in_progress' => 'หาสาเหตุรากเหง้า และสร้างมาตรการ',
                                            'pending_review' => 'รอหัวหน้าตรวจสอบและปิดมาตรการ',
                                            default => 'รอดำเนินการเปิด P-CAR'
                                        } }}
                                        @if($ticket->pcar_opened_at)
                                            (ผ่านไปแล้ว {{ $ticket->pcar_opened_at->diffForHumans(null, true) }})
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                    

                </div>

            </div>

        </div>
    </div>
</x-app-layout>

<style>
    @media print {
        body { background-color: white !important; }
        nav, header, footer { display: none !important; }
        main, .py-12, .max-w-7xl { padding: 0 !important; margin: 0 !important; max-width: none !important; width: 100% !important; }
        .shadow-sm, .rounded-lg, .rounded-md, .shadow-inner { box-shadow: none !important; border-radius: 0 !important; }
        .grid { display: block !important; }
        .md\:col-span-2 { width: 100% !important; margin-bottom: 20px; }
        /* Hide right column (Status/Timeline) or display it nicely */
        .md\:col-span-1 { width: 100% !important; page-break-before: always; }
        .print\:hidden, #comments-list, form[action*="comments"], form[action*="assign"], [x-data="{ editMode: false }"] { display: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('slaCountdown', (dueIsoString) => ({
            dueTime: new Date(dueIsoString).getTime(),
            timeLeft: '--:--:--',
            isOverdue: false,
            init() {
                this.update();
                setInterval(() => this.update(), 1000);
            },
            update() {
                const now = new Date().getTime();
                const distance = this.dueTime - now;
                
                this.isOverdue = distance < 0;
                
                const absDistance = Math.abs(distance);
                const days = Math.floor(absDistance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((absDistance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((absDistance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((absDistance % (1000 * 60)) / 1000);
                
                let timeString = '';
                if (days > 0) timeString += days + 'd ';
                timeString += String(hours).padStart(2, '0') + ':' + 
                              String(minutes).padStart(2, '0') + ':' + 
                              String(seconds).padStart(2, '0');
                              
                this.timeLeft = (this.isOverdue ? '-' : '') + timeString;
            }
        }));
    });
</script>

<!-- Live Monitor Polling Script for Comments -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setInterval(() => {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const currentContent = document.getElementById('comments-list');
                    const newContent = doc.getElementById('comments-list');
                    
                    if (currentContent && newContent) {
                        // Only update if content changed to avoid flicker
                        if(currentContent.innerHTML !== newContent.innerHTML) {
                            currentContent.innerHTML = newContent.innerHTML;
                            // Auto scroll to bottom
                            currentContent.scrollTop = currentContent.scrollHeight;
                        }
                    }
                })
                .catch(error => console.error('Error fetching live comments:', error));
        }, 15000); // 15 seconds
        
        // Initial scroll to bottom
        const commentsList = document.getElementById('comments-list');
        if(commentsList) {
            commentsList.scrollTop = commentsList.scrollHeight;
        }
    });
</script>
