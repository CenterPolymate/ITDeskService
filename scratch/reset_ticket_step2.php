<?php

use App\Models\HelpdeskCase;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$ticket = HelpdeskCase::where('ticket_no', 'IT-20260924-1206')->first();
if ($ticket) {
    $ticket->preventive_measure = 'pending_review';
    $ticket->pcar_closed_at = null;
    $ticket->pcar_closed_by = null;
    $ticket->save();
    echo 'TICKET_RESET_TO_PENDING_REVIEW';
} else {
    echo 'TICKET_NOT_FOUND';
}
