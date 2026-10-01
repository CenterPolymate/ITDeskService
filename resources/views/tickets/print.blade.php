<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P-CAR Report - {{ $ticket->ticket_no }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f3f4f6;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            font-size: 11px;
            line-height: 1.2;
            color: #000;
        }

        .legal-page {
            width: 216mm;
            height: 356mm;
            /* changed from min-height to height */
            background: white;
            margin: 0 auto;
            padding: 5mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        @page {
            size: Legal;
            margin: 5mm;
        }

        @media print {
            body {
                background: white;
            }

            .legal-page {
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
                /* changed from min-height */
            }

            .no-print {
                display: none;
            }
        }

        table {
            border-collapse: collapse;
        }

        td {
            border: 1px solid black;
            padding: 2px 4px;
        }

        .border border-black {
            border: 1px solid black;
        }

        . {
            border-top: 1px solid black;
        }

        . {
            border-bottom: 1px solid black;
        }

        . {
            border-left: 1px solid black;
        }

        . {
            border-right: 1px solid black;
        }

        .section-title {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }

        .checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid black;
            margin-right: 4px;
        }

        .val {
            color: #2563eb;
        }

        /* Blue color for dynamic values */

        .dotted-line {
            display: inline-block;
            border-bottom: 1px solid black;
            min-height: 14px;
        }

        .dotted-line {
            display: inline-block;
            border-bottom: 1px dotted black;
            min-height: 14px;
        }
    </style>
</head>

<body class="py-4">
    <div class="max-w-[216mm] mx-auto mb-4 no-print flex justify-between">
        <button onclick="window.history.back()"
            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            &larr; กลับ
        </button>
        <div>
            <a href="{{ route('tickets.export', $ticket->id) }}"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mr-2 inline-block">
                ดาวน์โหลด Excel
            </a>
            <button onclick="window.print()"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                พิมพ์ (Print)
            </button>
        </div>
    </div>

    <div class="legal-page">
        <!-- HEADER -->
        <table class="w-full border border-black border-b-0 text-[10px]" style="table-layout: fixed;">
            <tr>
                <td rowspan="2" class="text-center align-middle border-r-0" style="width: 20%; height: 50px;">
                    <div class="flex items-center justify-center h-full p-2">
                        <img src="{{ asset('images/polymate-logo.png') }}" alt="Polymate Logo"
                            class="max-h-[35px] w-auto object-contain">
                    </div>
                </td>
                <td rowspan="2" class="text-center align-middle font-bold text-[13px] border-l-0 px-1"
                    style="width: 48%;">
                    ใบรายงานการแก้ไขและป้องกันเพื่อซ่อม,ผลิต (FORM 4)<br>
                    <div class="text-[8px] font-normal uppercase leading-tight mt-1">MAINTENANCE CORRECTIVE AND
                        PREVENTIVE ACTION REPORT (P-CAR 4)</div>
                </td>
                <td class="p-1 border-b border-black align-middle" style="width: 22%;">
                    <div class="text-left whitespace-nowrap overflow-hidden flex items-center">
                        <span class="text-[10px]">P-CAR No.</span>
                        <span class="font-bold val ml-1 text-[11px]">{{ $ticket->ticket_no }}</span>
                    </div>
                </td>
                <td class="py-1 px-0 border-b border-black text-center text-[9px] align-middle tracking-tighter whitespace-nowrap overflow-hidden"
                    style="width: 5%;">ผู้ปฏิบัติ</td>
                <td class="py-1 px-0 border-b border-black text-center text-[9px] align-middle tracking-tighter whitespace-nowrap overflow-hidden"
                    style="width: 5%;">เวลา</td>
            </tr>
            <tr>
                <td class="text-center p-1 whitespace-nowrap text-[9px] align-middle">
                    <span class="checkbox"></span> BM
                    <span class="checkbox ml-1"></span> CM
                    <span class="checkbox ml-1"></span> KM
                </td>
                <td
                    class="py-1 px-0 text-center text-[9px] align-middle tracking-tighter whitespace-nowrap overflow-hidden">
                    วิศวกรรม</td>
                <td class="p-1 text-center text-[9px] align-middle">&nbsp;</td>
            </tr>
        </table>

        <!-- MAIN TABLE -->
        <table class="w-full border border-black border-t-0 text-[10px] flex-1" style="table-layout: fixed;">
            <colgroup>
                <col style="width: 8%;">
                <col style="width: 41%;">
                <col style="width: 41%;">
                <col style="width: 5%;">
                <col style="width: 5%;">
            </colgroup>

            <!-- SECTION 1 -->
            <tr>
                <td class="section-title  border-b border-black">สถานภาพ<br>การแจ้งซ่อม<br>,ผลิต<br>ครบถ้วน</td>
                <td colspan="2" class="p-0 border-b border-black align-top">
                    <table class="w-full h-full border-hidden">
                        <tr>
                            <td class="w-1/2 p-1 align-top border-r-0">
                                <div class="mb-[2px] flex items-end"><span class="w-16 shrink-0 inline-block">หน่วยงาน
                                        :</span> <span
                                        class="dotted-line flex-1 val text-left">{{ $ticket->department }}</span>
                                </div>
                                <div class="mb-[2px] flex items-end"><span
                                        class="w-16 shrink-0 inline-block">ชื่อผู้แจ้ง :</span> <span
                                        class="dotted-line flex-1 val text-left">{{ $ticket->requester_name }}</span>
                                </div>
                                <div class="mb-[2px] flex items-end"><span
                                        class="w-16 shrink-0 inline-block">ชื่อผู้ใช้งาน :</span> <span
                                        class="dotted-line flex-1 text-left val">คุณ{{ $ticket->user->name ?? $ticket->requester_name }}
                                        บริษัท {{ $ticket->user->company ?? '' }} -
                                        {{ $ticket->user->department ?? '' }}</span></div>
                                <div class="mb-[2px] flex items-end"><span
                                        class="w-16 shrink-0 inline-block">ชื่อเครื่อง :</span> <span
                                        class="dotted-line flex-1 text-left">&nbsp;</span></div>
                                <div class="flex items-end"><span class="w-16 shrink-0 inline-block">รหัสเครื่อง
                                        :</span> <span class="dotted-line flex-1 text-left">&nbsp;</span></div>
                            </td>
                            <td class="w-1/2 p-1 align-top border-l-0">
                                <div class="mb-2 whitespace-nowrap text-right pr-4">
                                    วันที่เครื่องขัดข้อง : <span
                                        class="dotted-line w-28 text-center val">{{ $ticket->created_at->locale('th')->translatedFormat('d F Y') }}</span>
                                    เวลา <span
                                        class="dotted-line w-16 text-center val">{{ $ticket->created_at->format('H:i') }}</span>
                                    น.
                                </div>
                                <div class="mb-1 ml-10"><span class="checkbox"></span> เครื่องจักรหยุด</div>
                                <div class="ml-10"><span class="checkbox"></span> เครื่องจักรไม่หยุด</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td rowspan="2" class="section-title  border-b border-black bg-white"
                    style="writing-mode: vertical-rl; transform: rotate(180deg);">ผู้พบปัญหา</td>
                <td rowspan="3" class="align-middle text-center text-[10px] border-b border-black">
                    <div style="margin-top: 60px;">T 1</div>
                </td>
            </tr>

            <!-- SECTION 2 Top -->
            <tr>
                <td rowspan="2" class="section-title  border-b border-black">
                    รายงาน<br>สถานภาพ<br>ของสิ่ง<br>ผิดปกติ<br>อย่างละเอียด</td>
                <td class="p-0 align-top">
                    <!-- หัวข้อปัญหา (2 บรรทัดตายตัว) -->
                    <div class="mb-[4px] px-1 flex mt-1">
                        <span class="font-bold whitespace-nowrap mr-2">หัวข้อปัญหา :</span>
                        <div class="flex-1 dotted-line val text-blue-700 leading-tight pt-[2px] pb-[1px] truncate">
                            {{ $ticket->title }}</div>
                    </div>
                    <div class="mb-[4px] px-1 flex">
                        <div class="flex-1 dotted-line leading-none pb-[2px]"></div>
                    </div>

                    <!-- อาการที่ขัดข้อง (หัวข้อ + 4 บรรทัดตายตัว) -->
                    <div class="px-1 font-bold h-[16px] flex items-end">
                        อาการที่ขัดข้อง :
                    </div>
                    <div class="relative w-full h-[80px] overflow-hidden mb-1">
                        <!-- 4 เส้นบรรทัดพื้นหลังตายตัว -->
                        <div class="absolute top-0 left-0 w-full h-full pointer-events-none flex flex-col px-1">
                            <div class="border-b border-dotted border-black w-full h-[20px]"></div>
                            <div class="border-b border-dotted border-black w-full h-[20px]"></div>
                            <div class="border-b border-dotted border-black w-full h-[20px]"></div>
                            <div class="border-b border-dotted border-black w-full h-[20px]"></div>
                        </div>
                        <!-- ข้อความที่จะไม่ขยายช่องเด็ดขาด -->
                        <div
                            class="relative z-10 px-2 pt-[2px] text-[10px] leading-[20px] val text-blue-700 break-words overflow-hidden h-full">
                            {!! nl2br(e($ticket->description)) !!}</div>
                    </div>
                    <div class="flex-1"></div>
                </td>
                <td class="p-1 border-b border-black align-top">
                    <div class="font-bold">ภาพประกอบ</div>
                    <div class="mt-1 flex justify-center items-center">
                        @if ($ticket->attachment_path)
                            <img src="{{ asset('storage/' . $ticket->attachment_path) }}" alt="Attachment"
                                class="max-w-full max-h-[60px] object-contain">
                        @endif
                    </div>
                </td>
            </tr>

            <!-- SECTION 2 Bottom -->
            <tr>
                <td class="p-2 border-b border-black border-r-0 align-top h-[75px]">
                    <div class="flex flex-col justify-start h-full pt-1">
                        <div class="flex items-end mb-1">
                            @php
                                $receivedAt = $ticket->assigned_at ?? $ticket->created_at;
                            @endphp
                            <span class="mr-2 text-[11px]">ลงชื่อ</span>
                            <div
                                class="flex-1 border-b border-dotted border-black text-center val text-[10px] leading-none pb-[2px]">
                                {{ $ticket->requester_name }}</div>
                            <div
                                class="w-28 border-b border-dotted border-black ml-4 text-center val text-[10px] leading-none pb-[2px]">
                                {{ $ticket->created_at->locale('th')->translatedFormat('d F Y') }}</div>
                        </div>
                        <div class="flex mt-1">
                            <span class="mr-2 invisible text-[11px]">ลงชื่อ</span>
                            <div class="flex-1 text-center text-[9px]">( ผู้ขออนุมัติการซ่อม,ผลิต )</div>
                            <div class="w-28 ml-4"></div>
                        </div>
                    </div>
                </td>
                <td class="p-2 border-b border-black border-l-0 align-top h-[75px]">
                    <div class="flex flex-col justify-between h-full pt-1">
                        <div>
                            <div class="flex items-end mb-1">
                                <span class="mr-2 text-[11px]">ลงชื่อ</span>
                                <div
                                    class="flex-1 border-b border-dotted border-black text-center val text-[10px] leading-none pb-[2px]">
                                    {{ $ticket->assignedBy ? $ticket->assignedBy->name : '' }}</div>
                                <div
                                    class="w-28 border-b border-dotted border-black ml-4 text-center val text-[10px] leading-none pb-[2px]">
                                    {{ $receivedAt->locale('th')->translatedFormat('d F Y') }}</div>
                            </div>
                            <div class="flex items-end mt-1">
                                <span class="mr-2 invisible text-[11px]">ลงชื่อ</span>
                                <div class="flex-1 text-center text-[9px] pb-[2px] whitespace-nowrap">( ผู้รับแจ้ง :
                                    ฝ่ายวิศวกรรม/ฝ่ายINS )</div>
                                <div class="w-28 ml-4 flex items-end text-[9px]">
                                    <span class="mr-1">เวลา</span>
                                    <div
                                        class="w-16 border-b border-dotted border-black text-center val leading-none pb-[2px]">
                                        {{ $receivedAt->format('H:i') }}</div>
                                    <span class="ml-1">น.</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-end justify-center mt-3 text-[9px]">
                            @php
                                $t1_minutes = $ticket->assigned_at
                                    ? $ticket->created_at
                                        ->copy()
                                        ->startOfMinute()
                                        ->diffInMinutes($ticket->assigned_at->copy()->startOfMinute())
                                    : '';
                            @endphp
                            <span>เวลาเริ่มหยุดจนถึงแจ้งซ่อม</span>
                            <div
                                class="w-16 border-b border-dotted border-black mx-2 text-center val leading-none pb-[2px]">
                                {{ $t1_minutes }}</div>
                            <span>นาที ( T1 )</span>
                        </div>
                    </div>
                </td>
                <td class="section-title  border-b border-black bg-white"
                    style="writing-mode: vertical-rl; transform: rotate(180deg);">วิศวกรรมและ<br>หน.งาน</td>
            </tr>

            <!-- SECTION 3 รอช่าง -->
            <tr>
                <td class="section-title  border-b border-black">รอช่าง</td>
                <td colspan="2" class="p-1 border-b border-black align-middle text-[9px]">
                    <div class="flex items-end justify-center px-1">
                        @php
                            $t2_minutes =
                                $ticket->analyzing_at && $ticket->assigned_at
                                    ? $ticket->assigned_at
                                        ->copy()
                                        ->startOfMinute()
                                        ->diffInMinutes($ticket->analyzing_at->copy()->startOfMinute())
                                    : '';
                        @endphp
                        <span class="mr-1">ช่างซ่อมถึงหน้างานวันที่</span>
                        <div class="w-28 dotted-line text-center val">
                            {{ $ticket->analyzing_at ? $ticket->analyzing_at->locale('th')->translatedFormat('d F Y') : '' }}
                        </div>
                        <span class="mx-1">เวลา</span>
                        <div class="w-16 dotted-line text-center val">
                            {{ $ticket->analyzing_at ? $ticket->analyzing_at->format('H:i') : '' }}</div><span
                            class="ml-1 mr-8">น.</span>
                        <span class="mr-1">รวม</span>
                        <div class="w-16 dotted-line text-center val leading-none pb-[2px]">{{ $t2_minutes }}</div>
                        <span class="ml-1">นาที ( T2 )</span>
                    </div>
                </td>
                <td class=" border-b border-black">&nbsp;</td>
                <td class="text-center align-middle text-[10px] border-b border-black">T 2</td>
            </tr>

            <!-- SECTION 4 วิเคราะห์ -->
            <tr>
                <td rowspan="2" class="section-title  border-b border-black">ร่วมวิเคราะห์<br>หาสาเหตุ</td>
                <td colspan="2" class="p-1 border-b border-black align-top">
                    <div class="mb-[2px]">การวิเคราะห์หาสาเหตุ</div>
                    <div class="mb-[2px] pl-4 flex"><span class="w-8 whitespace-nowrap mr-2">ทำไม 1</span>
                        <div class="flex-1 dotted-line val px-2 truncate">{{ $ticket->why_1 }}</div>
                    </div>
                    <div class="mb-[2px] pl-4 flex"><span class="w-8 whitespace-nowrap mr-2">ทำไม 2</span>
                        <div class="flex-1 dotted-line val px-2 truncate">{{ $ticket->why_2 }}</div>
                    </div>
                    <div class="mb-[2px] pl-4 flex"><span class="w-8 whitespace-nowrap mr-2">ทำไม 3</span>
                        <div class="flex-1 dotted-line val px-2 truncate">{{ $ticket->why_3 }}</div>
                    </div>
                    <div class="mt-1 flex items-start">
                        <span class="w-28 shrink-0 text-right pr-1">สาเหตุรากเหง้า :</span>
                        <div class="flex-1 dotted-line val px-2 break-words leading-none min-h-[14px] pb-[2px]">
                            {{ $ticket->root_cause_detail }}
                        </div>
                    </div>
                    <div class="mt-1 flex flex-col">
                        <div class="flex mb-1">
                            <span class="w-28 shrink-0 text-right pr-1">ผู้ปฏิบัติงาน : 1.</span>
                            <div class="flex-1 dotted-line val px-2 truncate pb-[2px]">
                                {{ $ticket->pcarAnalyzedBy ? $ticket->pcarAnalyzedBy->name : '' }}</div>
                            <span class="w-12 ml-2 shrink-0">หน่วยงาน</span>
                            <div class="flex-1 dotted-line val px-2 truncate pb-[2px]">
                                {{ $ticket->pcarAnalyzedBy ? $ticket->pcarAnalyzedBy->department : '' }}</div>
                        </div>
                        <div class="flex">
                            <span class="w-28 shrink-0 text-right pr-1">2.</span>
                            <div class="flex-1 dotted-line val px-2 truncate pb-[2px]">
                                {{ $ticket->pcarClosedBy ? $ticket->pcarClosedBy->name : '' }}</div>
                            <span class="w-12 ml-2 shrink-0">หน่วยงาน</span>
                            <div class="flex-1 dotted-line val px-2 truncate pb-[2px]">
                                {{ $ticket->pcarClosedBy ? $ticket->pcarClosedBy->department : '' }}</div>
                        </div>
                    </div>
                </td>
                <td rowspan="2" class="section-title  border-b border-black bg-white"
                    style="writing-mode: vertical-rl; transform: rotate(180deg);">วิศวกรรม</td>
                <td rowspan="2" class="align-middle text-center text-[10px] border-b border-black">T 3</td>
            </tr>
            <tr>
                @php
                    $t3_val =
                        $ticket->pcar_analyzed_at && $ticket->pcar_opened_at
                            ? $ticket->pcar_opened_at
                                ->copy()
                                ->startOfMinute()
                                ->diffInMinutes($ticket->pcar_analyzed_at->copy()->startOfMinute())
                            : null;
                    $calculated_finish =
                        $ticket->analyzing_at && $t3_val !== null
                            ? $ticket->analyzing_at->copy()->addMinutes($t3_val)
                            : null;
                @endphp
                <td colspan="2" class="p-1 border-b border-black text-center text-[9px]">
                    เสร็จวันที่ <span
                        class="dotted-line w-28 text-blue-700">{{ $calculated_finish ? $calculated_finish->locale('th')->translatedFormat('d F Y') : '' }}</span>
                    <span class="ml-2">เวลา</span> <span
                        class="dotted-line w-16 text-blue-700">{{ $calculated_finish ? $calculated_finish->format('H:i') : '' }}</span>
                    น.
                    <span class="ml-4">ระยะเวลาวิเคราะห์</span> <span
                        class="dotted-line w-16 text-blue-700">{{ $t3_val !== null ? $t3_val : '' }}</span> นาที ( T3
                    )
                </td>
            </tr>

            <!-- SECTION 5 วัสดุ -->
            <tr>
                <td rowspan="2" class="section-title  border-b border-black">
                    ลงรายการ<br>วัสดุ/อะไหล่<br>ให้ครบถ้วน<br>100 %.</td>
                <td colspan="2" class="p-0 border-b border-black align-top">
                    <div class="text-center font-bold border-b border-black text-[9px] py-[1px]">
                        รายละเอียดค่าใช้จ่ายการซ่อม,วัสดุ / อะไหล่ที่ใช้</div>
                    <table class="w-full text-center border-hidden text-[8px]">
                        <tr class="border-b border-black">
                            <td class="w-[8%] border-r border-black p-[1px]">ลำดับ</td>
                            <td class="w-[30%] border-r border-black p-[1px]">รายการวัสดุที่ใช้</td>
                            <td class="w-[15%] border-r border-black p-[1px]">เอกสารเบิก<br>เลขที่ใบขอซื้อ</td>
                            <td class="w-[12%] border-r border-black p-[1px]">ซื้อจาก<br>บริษัท</td>
                            <td class="w-[10%] border-r border-black p-[1px]">ราคา<br>ต่อหน่วย</td>
                            <td class="w-[10%] border-r border-black p-[1px]">จำนวนเงิน</td>
                            <td class="p-[1px]">หมายเหตุ</td>
                        </tr>
                        @for ($i = 0; $i < 4; $i++)
                            <tr class="border-b border-black h-4">
                                <td class="border-r border-black"></td>
                                <td class="border-r border-black"></td>
                                <td class="border-r border-black"></td>
                                <td class="border-r border-black"></td>
                                <td class="border-r border-black"></td>
                                <td class="border-r border-black"></td>
                                <td></td>
                            </tr>
                        @endfor
                        <tr>
                            <td colspan="3" class="border-r border-black p-[1px]"></td>
                            <td colspan="2" class="border-r border-black bg-gray-100 p-[1px]">ยอดเงินรวม</td>
                            <td class="border-r border-black p-[1px]"></td>
                            <td class="p-[1px]"></td>
                        </tr>
                    </table>
                </td>
                <td rowspan="2" class="section-title  border-b border-black bg-white"
                    style="writing-mode: vertical-rl; transform: rotate(180deg);">วิศวกรรม</td>
                <td rowspan="2" class="align-middle text-center text-[10px] border-b border-black">T 4</td>
            </tr>
            <tr>
                <td colspan="2" class="p-1 border-b border-black text-center text-[9px] whitespace-nowrap">
                    เริ่มวันที่ <span class="dotted-line w-28"></span> <span class="ml-2">เวลา</span> <span
                        class="dotted-line w-16"></span> น.
                    <span class="ml-2">เสร็จวันที่</span> <span class="dotted-line w-28"></span> <span
                        class="ml-2">เวลา</span> <span class="dotted-line w-16"></span> น.
                    <span class="ml-2">ระยะเวลาซ่อมแซม</span> <span class="dotted-line w-12"></span> นาที ( T4 )
                </td>
            </tr>

            <!-- SECTION 6 แก้ไข -->
            <tr>
                <td rowspan="3" class="section-title  border-b border-black">ดำเนินการ<br>แก้ไข</td>
                <td colspan="2" class="p-0 pt-1 align-top">
                    <div class="mb-[4px] px-1 flex mt-1">
                        <span class="mr-2 whitespace-nowrap">การแก้ไข/ซ่อม :</span>
                        <div class="flex-1 dotted-line val text-blue-700 leading-tight pt-[2px] pb-[1px] truncate">
                            {{ $ticket->resolution_notes }}</div>
                    </div>
                    <div class="mb-[4px] px-1 flex">
                        <span class="mr-2 whitespace-nowrap invisible">การแก้ไข/ซ่อม :</span>
                        <div class="flex-1 dotted-line leading-none pb-[2px]"></div>
                    </div>
                    <div class="mb-[4px] px-1 flex">
                        <span class="mr-2 whitespace-nowrap invisible">การแก้ไข/ซ่อม :</span>
                        <div class="flex-1 dotted-line leading-none pb-[2px]"></div>
                    </div>
                </td>
                <td rowspan="3" class="section-title  border-b border-black bg-white"
                    style="writing-mode: vertical-rl; transform: rotate(180deg);">วิศวกรรม</td>
                <td rowspan="3" class="align-middle text-center text-[10px] border-b border-black">T 5</td>
            </tr>
            <tr>
                <td class="p-1 border-r-0 border-black align-top border-b-0 pb-0">
                    <div class="flex mt-1 px-4">
                        <span class="mr-2">ลงชื่อ</span>
                        <div class="flex-1 dotted-line val text-blue-700 text-center pb-[2px]">
                            {{ $ticket->resolvedBy ? $ticket->resolvedBy->name : '' }}</div><span
                            class="ml-2">ผู้ส่งมอบงาน.</span>
                    </div>
                </td>
                <td class="p-1 border-l-0 border-black align-top border-b-0 pb-0">
                    <div class="flex mt-1 px-4">
                        <span class="mr-2">ลงชื่อ</span>
                        <div class="flex-1 dotted-line val text-blue-700 text-center pb-[2px]">
                            {{ $ticket->requester_name }}</div><span class="ml-2">ผู้รับงาน</span>
                    </div>
                </td>
            </tr>
            <tr>
                @php
                    $t5_val =
                        $ticket->in_progress_at && $ticket->resolved_at
                            ? $ticket->in_progress_at
                                ->copy()
                                ->startOfMinute()
                                ->diffInMinutes($ticket->resolved_at->copy()->startOfMinute())
                            : null;
                    $t5_start = $calculated_finish;
                    $t5_finish = $t5_start && $t5_val !== null ? $t5_start->copy()->addMinutes($t5_val) : null;
                @endphp
                <td colspan="2"
                    class="p-1 border-b border-t-0 border-black text-center text-[9px] whitespace-nowrap">
                    เริ่มซ่อมวันที่ <span
                        class="dotted-line w-28 text-blue-700">{{ $t5_start ? $t5_start->locale('th')->translatedFormat('d F Y') : '' }}</span>
                    เวลา <span
                        class="dotted-line w-8 text-blue-700">{{ $t5_start ? $t5_start->format('H:i') : '' }}</span>
                    น.
                    <span class="ml-2">เสร็จวันที่</span> <span
                        class="dotted-line w-28 text-blue-700">{{ $t5_finish ? $t5_finish->locale('th')->translatedFormat('d F Y') : '' }}</span>
                    เวลา <span
                        class="dotted-line w-8 text-blue-700">{{ $t5_finish ? $t5_finish->format('H:i') : '' }}</span>
                    น.
                    <span class="ml-2">เวลารวม</span> <span
                        class="dotted-line w-10 text-blue-700">{{ $t5_val !== null ? $t5_val : '' }}</span> นาที ( T5
                    )
                </td>
            </tr>

            <!-- SECTION 7 ทดสอบ -->
            <tr>
                <td rowspan="2" class="section-title border-b border-black">ทดสอบและ<br>ส่งมอบงาน</td>
                <td class="p-1 border-r-0 border-black align-top border-b-0 pb-0">
                    <div class="flex mt-1 px-4">
                        <span class="mr-2">ลงชื่อ</span>
                        <div class="flex-1 dotted-line"></div><span class="ml-2">ผู้ส่งมอบงาน.</span>
                    </div>
                </td>
                <td class="p-1 border-l-0 border-black align-top border-b-0 pb-0">
                    <div class="flex mt-1 px-4">
                        <span class="mr-2">ลงชื่อ</span>
                        <div class="flex-1 dotted-line"></div><span class="ml-2">ผู้รับงาน</span>
                    </div>
                </td>
                <td rowspan="2" class="section-title border-b border-black bg-white"
                    style="writing-mode: vertical-rl; transform: rotate(180deg);">ผู้ใช้งาน</td>
                <td rowspan="2" class="align-middle text-center text-[10px] border-b border-black">T 6</td>
            </tr>
            <tr>
                <td colspan="2"
                    class="p-1 border-b border-t-0 border-black text-center text-[9px] whitespace-nowrap">
                    ตรวจรับวันที่ <span class="dotted-line w-28"></span>
                    <span class="ml-2">เวลา</span> <span class="dotted-line w-16"></span> น.
                    <span class="ml-2">เวลาในการทดสอบ</span> <span class="dotted-line w-16"></span> นาที ( T6 )
                </td>
            </tr>

            <!-- SECTION 8 ปิดใบงาน -->
            <tr>
                <td class="section-title  border-b border-black">ปิดใบงาน</td>
                <td class="p-1 border-r border-b border-black align-top">
                    <div class="flex mt-1 px-1">
                        <span class="mr-1">ลงชื่อ</span>
                        <div class="flex-1 dotted-line val text-blue-700 text-center pb-[2px]">
                            {{ $ticket->pcarOpenedBy ? $ticket->pcarOpenedBy->name : '' }}</div><span
                            class="ml-1">ผู้จัดการแผนก ซ่อม/สร้าง (BM)</span>
                    </div>
                    <div class="flex mt-1 px-1">
                        <span class="mr-1 ml-6">วันที่</span>
                        <div class="w-28 dotted-line val text-blue-700 text-center pb-[2px]">
                            {{ $ticket->pcar_opened_at ? $ticket->pcar_opened_at->locale('th')->translatedFormat('d F Y') : '' }}
                        </div>
                    </div>
                    <div class="mt-1 flex justify-center gap-4 text-[9px]">
                        <div class="flex items-center"><span
                                class="checkbox flex items-center justify-center font-bold text-[10px]">{{ $ticket->requires_preventive_measure === true ? '✓' : '' }}</span>
                            มีมาตรการป้องกัน</div>
                        <div class="flex items-center"><span
                                class="checkbox flex items-center justify-center font-bold text-[10px]">{{ $ticket->requires_preventive_measure === false ? '✓' : '' }}</span>
                            ไม่มีมาตรการป้องกัน</div>
                    </div>
                </td>
                <td class="p-1 border-b border-black align-middle text-center">
                    @php
                        $t3_minutes =
                            $ticket->analyzing_at && $ticket->in_progress_at
                                ? $ticket->analyzing_at
                                    ->copy()
                                    ->startOfMinute()
                                    ->diffInMinutes($ticket->in_progress_at->copy()->startOfMinute())
                                : null;
                        $t4_minutes =
                            $ticket->in_progress_at && $ticket->resolved_at
                                ? $ticket->in_progress_at
                                    ->copy()
                                    ->startOfMinute()
                                    ->diffInMinutes($ticket->resolved_at->copy()->startOfMinute())
                                : null;
                        $t5_minutes =
                            $ticket->resolved_at && $ticket->approved_at
                                ? $ticket->resolved_at
                                    ->copy()
                                    ->startOfMinute()
                                    ->diffInMinutes($ticket->approved_at->copy()->startOfMinute())
                                : null;
                        $t6_minutes =
                            $ticket->approved_at && $ticket->closed_at
                                ? $ticket->approved_at
                                    ->copy()
                                    ->startOfMinute()
                                    ->diffInMinutes($ticket->closed_at->copy()->startOfMinute())
                                : null;

                        $t_values = array_filter(
                            [
                                isset($t1_minutes) && is_numeric($t1_minutes) ? $t1_minutes : null,
                                isset($t2_minutes) && is_numeric($t2_minutes) ? $t2_minutes : null,
                                $t3_minutes,
                                $t4_minutes,
                                $t5_minutes,
                                $t6_minutes,
                            ],
                            'is_numeric',
                        );

                        $total_minutes = count($t_values) > 0 ? array_sum($t_values) : '';
                    @endphp
                    เวลารวม ( T1+T2+T3+T4+T5+T6 ) = <div
                        class="w-16 border-b border-dotted border-black inline-block mx-1 text-center val leading-none pb-[2px]">
                        {{ $total_minutes }}</div> นาที
                </td>
                <td class="section-title  border-b border-black bg-white"
                    style="writing-mode: vertical-rl; transform: rotate(180deg);">หน.แผนก</td>
                <td class="align-middle text-center text-[10px] border-b border-black"
                    style="writing-mode: vertical-rl; transform: rotate(180deg);">T รวม</td>
            </tr>

            <!-- SECTION 9 ป้องกัน -->
            <tr>
                <td rowspan="2" class="section-title  border-b border-black">ดำเนินการ<br>ป้องกัน</td>
                <td colspan="2" class="p-0 border-b border-black">
                    <table class="w-full h-full text-center border-hidden text-[9px]" style="table-layout: fixed;">
                        <colgroup>
                            <col style="width: 24px;">
                            <col>
                            <col style="width: 20%;">
                            <col style="width: 30%;">
                        </colgroup>
                        <tr class="border-b border-black font-bold">
                            <td colspan="2" class="border-r border-black p-1">มาตรการป้องกัน</td>
                            <td class="border-r border-black p-1">กำหนดเสร็จ</td>
                            <td class="p-1">ผู้รับผิดชอบ</td>
                        </tr>
                        <!-- เฉพาะกรณี 4 rows -->
                        <tr class="h-5">
                            <td rowspan="4" class="w-6 border-r border-b border-black section-title"
                                style="writing-mode: vertical-rl; transform: rotate(180deg);">เฉพาะกรณี</td>
                            <td class="border-r border-black border-b"
                                style="border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-r border-black border-b"
                                style="border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-b" style="border-bottom-style: dotted;"></td>
                        </tr>
                        <tr class="h-5">
                            <td class="border-r border-black border-b"
                                style="border-top: none; border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-r border-black border-b"
                                style="border-top: none; border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-b" style="border-top: none; border-bottom-style: dotted;"></td>
                        </tr>
                        <tr class="h-5">
                            <td class="border-r border-black border-b"
                                style="border-top: none; border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-r border-black border-b"
                                style="border-top: none; border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-b" style="border-top: none; border-bottom-style: dotted;"></td>
                        </tr>
                        <tr class="h-5 border-b border-black" style="border-bottom-style: solid;">
                            <td class="border-r border-black" style="border-top: none; border-right-style: solid;">
                            </td>
                            <td class="border-r border-black" style="border-top: none; border-right-style: solid;">
                            </td>
                            <td style="border-top: none;"></td>
                        </tr>
                        <!-- ทั้งระบบ 4 rows -->
                        <tr class="h-5">
                            <td rowspan="4" class="w-6 border-r border-b border-black section-title"
                                style="writing-mode: vertical-rl; transform: rotate(180deg);">ทั้งระบบ</td>
                            <td class="border-r border-black border-b"
                                style="border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-r border-black border-b"
                                style="border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-b" style="border-bottom-style: dotted;"></td>
                        </tr>
                        <tr class="h-5">
                            <td class="border-r border-black border-b"
                                style="border-top: none; border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-r border-black border-b"
                                style="border-top: none; border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-b" style="border-top: none; border-bottom-style: dotted;"></td>
                        </tr>
                        <tr class="h-5">
                            <td class="border-r border-black border-b"
                                style="border-top: none; border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-r border-black border-b"
                                style="border-top: none; border-bottom-style: dotted; border-right-style: solid;"></td>
                            <td class="border-b" style="border-top: none; border-bottom-style: dotted;"></td>
                        </tr>
                        <tr class="h-5">
                            <td class="border-r border-b-0 border-black"
                                style="border-top: none; border-right-style: solid; border-bottom: none;"></td>
                            <td class="border-r border-b-0 border-black"
                                style="border-top: none; border-right-style: solid; border-bottom: none;"></td>
                            <td class="border-b-0" style="border-top: none; border-bottom: none;"></td>
                        </tr>
                    </table>
                </td>
                <td rowspan="2" class="section-title  border-b border-black bg-white"
                    style="writing-mode: vertical-rl; transform: rotate(180deg);">ฝ่ายวิศวกรรม สนับสนุน</td>
                <td rowspan="2" class="align-middle text-center text-[10px] border-b border-black">&nbsp;</td>
            </tr>
            <tr>
                <td colspan="2" class="p-0 border-b border-black align-top">
                    <div class="text-center font-bold border-b border-black text-[9px] py-[1px]">
                        สรุปปัญหา/สาเหตุเกิดจาก</div>
                    <table class="w-full h-full text-center border-hidden text-[8px]">
                        <tr class="border-b border-black">
                            <td class="border-r border-black p-[2px]"><span class="checkbox"></span> คน</td>
                            <td class="border-r border-black p-[2px]"><span class="checkbox"></span> เครื่องจักร</td>
                            <td class="border-r border-black p-[2px]"><span class="checkbox"></span> วัสดุ</td>
                            <td class="border-r border-black p-[2px]"><span class="checkbox"></span> วิธีการ</td>
                            <td class="p-[2px]"><span class="checkbox"></span> สิ่งแวดล้อม</td>
                        </tr>
                        <tr class="align-top">
                            <td class="border-r border-black p-[2px] text-left pl-1 pt-1">
                                <div class="h-[14px] flex items-center mb-1"><span class="checkbox"></span>
                                    ไม่รู้มาตรฐาน</div>
                                <div class="h-[14px] flex items-center mb-1"><span class="checkbox"></span>
                                    ไม่ทำตามมาตรฐาน</div>
                                <div class="h-[14px] flex items-center"><span class="checkbox"></span>
                                    ทำตามแล้วยังเกิด</div>
                            </td>
                            <td class="border-r border-black p-[2px] text-left pl-1 pt-1">
                                <div class="h-[14px] flex items-center mb-1"><span class="checkbox"></span>
                                    ออกแบบไม่ดี</div>
                                <div class="h-[14px] flex items-center mb-1"><span class="checkbox"></span>
                                    ไม่ได้กำหนดมาตรฐาน</div>
                            </td>
                            <td class="border-r border-black p-[2px] text-left pl-1 pt-1">
                                <div class="h-[14px] flex items-center mb-1"><span class="checkbox"></span>
                                    ไม่ได้กำหนดมาตรฐาน</div>
                                <div class="h-[14px] flex items-center mb-1"><span class="checkbox"></span>
                                    กำหนดไม่เหมาะสม</div>
                            </td>
                            <td class="border-r border-black p-[2px] text-left pl-1 pt-1">
                                <div class="h-[14px] flex items-center mb-1"><span class="checkbox"></span>
                                    ไม่มีมาตรฐาน</div>
                                <div class="h-[14px] flex items-center mb-1"><span class="checkbox"></span>
                                    กำหนดไม่เหมาะสม</div>
                            </td>
                            <td class="p-[2px] text-left pl-1 pt-1">
                                <div class="h-[14px] flex items-center mb-1"><span class="checkbox"></span>
                                    ไม่มีมาตรฐาน</div>
                                <div class="h-[14px] flex items-center mb-1"><span class="checkbox"></span>
                                    กำหนดไม่เหมาะสม</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- SECTION 10 ปิดมาตรการ -->
            <tr>
                <td class="section-title  border-b-0 border-black">ปิดมาตรการ<br>ป้องกัน</td>
                <td class="p-1 border-r border-b-0 border-black align-top">
                    <div class="flex mt-4 px-4 text-[9px]">
                        <span class="mr-2">ลงชื่อ</span>
                        <div class="flex-1 dotted-line border-black"></div><span
                            class="ml-2">ผจก.ฝ่ายที่รับผิดชอบ</span>
                    </div>
                    <div class="flex mt-1 px-4 text-[9px]">
                        <span class="mr-2">วันที่</span>
                        <div class="w-28 dotted-line border-black"></div>
                    </div>
                </td>
                <td class="p-1 border-b-0 border-black align-top">
                    <div class="flex mt-4 px-4 text-[9px]">
                        <span class="mr-2">ลงชื่อ</span>
                        <div class="flex-1 dotted-line border-black"></div><span class="ml-2">Asst. QMR/QMR</span>
                    </div>
                    <div class="flex mt-1 px-4 text-[9px]">
                        <span class="mr-2">วันที่</span>
                        <div class="w-28 dotted-line border-black"></div>
                    </div>
                </td>
                <td class="section-title  border-b-0 border-black bg-white"
                    style="writing-mode: vertical-rl; transform: rotate(180deg);">QMR</td>
                <td class="border-b-0 border-black">&nbsp;</td>
            </tr>
        </table>

        <!-- FOOTER INFO -->
        <div class="flex justify-between text-[8px] mt-1 px-1 font-bold">
            <div>วันที่มีผลบังคับใช้ 15 มกราคม 2561</div>
            <div>ISSUE : C</div>
            <div>FS - A13 - 004</div>
        </div>
    </div>
</body>

</html>
