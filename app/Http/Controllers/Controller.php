<?php

namespace App\Http\Controllers;

use Illuminate\Http\UploadedFile;

abstract class Controller
{
    /**
     * อ่านไฟล์ CSV ที่อัปโหลด แล้วคืนค่าเป็นอาร์เรย์ของแถวข้อมูล (ข้ามแถวหัวตาราง)
     * รองรับ UTF-8 (มี/ไม่มี BOM), Windows-874 (ANSI ภาษาไทยจาก Excel) และตัวคั่น , ; หรือ Tab
     *
     * @return array<int, array<int, string>>
     */
    protected function readCsvRows(UploadedFile $file): array
    {
        $content = (string) file_get_contents($file->getRealPath());

        // ตัด BOM ของ UTF-8 ออก
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // แปลงไฟล์ที่บันทึกแบบ ANSI (Windows-874/TIS-620) เป็น UTF-8 (mbstring ไม่รองรับ CP874 จึงใช้ iconv)
        if (! mb_check_encoding($content, 'UTF-8')) {
            $converted = @iconv('CP874', 'UTF-8//IGNORE', $content);
            $content = $converted !== false ? $converted : mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        // ปรับบรรทัดใหม่ให้เป็นรูปแบบเดียวกัน
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // ตรวจหาตัวคั่นจากบรรทัดแรก (Excel บางภาษาใช้ ;)
        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = ',';
        $maxCount = substr_count($firstLine, ',');
        foreach ([';', "\t"] as $candidate) {
            if (substr_count($firstLine, $candidate) > $maxCount) {
                $delimiter = $candidate;
                $maxCount = substr_count($firstLine, $candidate);
            }
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        $isHeader = true;

        while (($data = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if ($isHeader) {
                $isHeader = false;

                continue;
            }

            $data = array_map(function (?string $value): string {
                $value = trim((string) $value);

                return $value === '-' ? '' : $value;
            }, $data);

            // ข้ามบรรทัดว่าง
            if (implode('', $data) === '') {
                continue;
            }

            $rows[] = $data;
        }

        fclose($handle);

        return $rows;
    }
}
