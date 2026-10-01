<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns="http://www.w3.org/TR/REC-html40">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Tahoma, sans-serif;
            font-size: 10pt;
        }

        td,
        th {
            border: 1px solid black;
            padding: 5px;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .bg-gray {
            background-color: #f3f4f6;
        }

        .no-border {
            border: none !important;
        }

        .val {
            color: blue;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td colspan="5" class="text-center font-bold" style="font-size: 14pt;">
                ใบรายงานการแก้ไขและป้องกันเพื่อซ่อม,ผลิต (FORM 4)</td>
        </tr>
        <tr>
            <td colspan="5" class="text-center" style="font-size: 10pt;">MAINTENANCE CORRECTIVE AND PREVENTIVE ACTION
                REPORT (P-CAR 4)</td>
        </tr>
        <tr>
            <td colspan="3" class="font-bold">P-CAR No. <span class="val">{{ $ticket->ticket_no }}</span></td>
            <td class="text-center">ผู้ปฏิบัติ</td>
            <td class="text-center">เวลา</td>
        </tr>
        <tr>
            <td colspan="3">
                [ ] BM &nbsp;&nbsp; [ ] CM &nbsp;&nbsp; [ ] KM
            </td>
            <td class="text-center">วิศวกรรม</td>
            <td></td>
        </tr>

        <!-- Section 1 -->
        <tr>
            <td rowspan="2" class="text-center font-bold">สถานภาพ<br>การแจ้งซ่อม<br>,ผลิต<br>ครบถ้วน</td>
            <td colspan="2">
                หน่วยงาน : <span class="val">{{ $ticket->department }}</span><br>
                ชื่อผู้แจ้ง : <span class="val">{{ $ticket->requester_name }}</span><br>
                ชื่อผู้ใช้งาน : <span class="val">คุณ{{ $ticket->user->name ?? $ticket->requester_name }} บริษัท
                    {{ $ticket->user->company ?? '' }} - {{ $ticket->user->department ?? '' }}</span><br>
                ชื่อเครื่อง : <br>
                รหัสเครื่อง :
            </td>
            <td colspan="2">
                วันที่เครื่องขัดข้อง : <span
                    class="val">{{ $ticket->created_at->locale('th')->translatedFormat('d F Y') }}</span><br>
                เวลา <span class="val">{{ $ticket->created_at->format('H:i') }}</span> น.<br>
                [ ] เครื่องจักรหยุด<br>
                [ ] เครื่องจักรไม่หยุด
            </td>
        </tr>
        <tr>
            <td colspan="2" class="text-center font-bold">ผู้พบปัญหา</td>
            <td colspan="2" class="text-center">T 1</td>
        </tr>

        <!-- Section 2 -->
        <tr>
            <td class="text-center font-bold">รายงาน<br>สถานภาพ<br>ของสิ่ง<br>ผิดปกติ<br>อย่างละเอียด</td>
            <td colspan="3">
                <b>หัวข้อปัญหา :</b> <span class="val">{{ $ticket->title }}</span><br>
                <b>อาการที่ขัดข้อง :</b><br>
                <span class="val">{!! nl2br(e($ticket->description)) !!}</span>
            </td>
            <td class="text-center">
                <b>ภาพประกอบ</b><br>
                @if ($ticket->attachment_path)
                    (มีไฟล์แนบในระบบ)
                @endif
            </td>
        </tr>
        <tr>
            <td class="text-center font-bold">การอนุมัติ</td>
            <td colspan="2">
                ลงชื่อ <span class="val">{{ $ticket->requester_name }}</span><br>
                วันที่ <span
                    class="val">{{ $ticket->created_at->locale('th')->translatedFormat('d F Y') }}</span><br>
                ( ผู้ขออนุมัติการซ่อม,ผลิต )
            </td>
            <td colspan="2">
                @php $receivedAt = $ticket->assigned_at ?? $ticket->created_at; @endphp
                ลงชื่อ <span class="val">{{ $ticket->assignedBy ? $ticket->assignedBy->name : '' }}</span><br>
                วันที่ <span class="val">{{ $receivedAt->locale('th')->translatedFormat('d F Y') }}</span><br>
                ( ผู้รับแจ้ง : ฝ่ายวิศวกรรม/ฝ่ายINS )<br>
                เวลา <span class="val">{{ $receivedAt->format('H:i') }}</span> น.
            </td>
        </tr>

        <!-- Section 3 -->
        <tr>
            <td class="text-center font-bold">รอช่าง</td>
            <td colspan="2">
                ช่างซ่อมถึงหน้างานวันที่ <span
                    class="val">{{ $ticket->analyzing_at ? $ticket->analyzing_at->locale('th')->translatedFormat('d F Y') : '' }}</span>
                เวลา <span
                    class="val">{{ $ticket->analyzing_at ? $ticket->analyzing_at->format('H:i') : '' }}</span> น.
            </td>
            <td colspan="2" class="text-center">T 2</td>
        </tr>

        <!-- Section 4 -->
        <tr>
            <td class="text-center font-bold">ร่วมวิเคราะห์<br>หาสาเหตุ</td>
            <td colspan="2">
                การวิเคราะห์หาสาเหตุ<br>
                ทำไม 1: <span class="val">{{ $ticket->why_1 }}</span><br>
                ทำไม 2: <span class="val">{{ $ticket->why_2 }}</span><br>
                ทำไม 3: <span class="val">{{ $ticket->why_3 }}</span><br>
                สาเหตุรากเหง้า : <span class="val">{{ $ticket->root_cause_detail }}</span><br>
                ผู้ปฏิบัติงาน : 1. <span
                    class="val">{{ $ticket->pcarAnalyzedBy ? $ticket->pcarAnalyzedBy->name : '' }}</span>
                2. <span class="val">{{ $ticket->pcarClosedBy ? $ticket->pcarClosedBy->name : '' }}</span>
            </td>
            <td colspan="2" class="text-center">T 3</td>
        </tr>

        <!-- Section 6 -->
        <tr>
            <td class="text-center font-bold">ดำเนินการ<br>แก้ไข</td>
            <td colspan="2">
                การแก้ไข/ซ่อม : <br>
                <span class="val">{{ $ticket->resolution_notes }}</span>
            </td>
            <td colspan="2" class="text-center">T 5</td>
        </tr>
        <tr>
            <td class="text-center font-bold">การส่งมอบ</td>
            <td colspan="2">
                ลงชื่อ <span class="val">{{ $ticket->resolvedBy ? $ticket->resolvedBy->name : '' }}</span>
                (ผู้ส่งมอบงาน)<br>
                ลงชื่อ <span class="val">{{ $ticket->requester_name }}</span> (ผู้รับงาน)
            </td>
            <td colspan="2"></td>
        </tr>

        <!-- Section 8 -->
        <tr>
            <td class="text-center font-bold">ปิดใบงาน</td>
            <td colspan="2">
                ลงชื่อ <span class="val">{{ $ticket->pcarOpenedBy ? $ticket->pcarOpenedBy->name : '' }}</span>
                (ผู้จัดการแผนก ซ่อม/สร้าง (BM))<br>
                วันที่ <span
                    class="val">{{ $ticket->pcar_opened_at ? $ticket->pcar_opened_at->locale('th')->translatedFormat('d F Y') : '' }}</span><br>
                มาตรการป้องกัน: [ {{ $ticket->requires_preventive_measure === true ? 'X' : ' ' }} ] มี [
                {{ $ticket->requires_preventive_measure === false ? 'X' : ' ' }} ] ไม่มี
            </td>
            <td colspan="2" class="text-center font-bold">T รวม</td>
        </tr>
    </table>
</body>

</html>
