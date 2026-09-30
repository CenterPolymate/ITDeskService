<?php
use App\Models\HelpdeskCase;

$t = HelpdeskCase::where('ticket_no', 'IT-202609-0001')->first();
if ($t) {
    $t->description = "What: เปิดคอมพิวเตอร์ไม่ติด ไม่มีภาพขึ้นหน้าจอ\nWhere/When: \nWhy/How: ";
    $t->save();
    echo "Updated IT-202609-0001 to the new format.\n";
} else {
    echo "Ticket not found.\n";
}
