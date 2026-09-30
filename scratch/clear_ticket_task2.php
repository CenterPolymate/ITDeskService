<?php

use App\Models\HelpdeskCase;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$ticket = HelpdeskCase::where('ticket_no', 'IT-20260924-1206')->first();
if ($ticket) {
    $ticket->why_1 = null;
    $ticket->why_2 = null;
    $ticket->why_3 = null;
    $ticket->root_cause_category = null;
    $ticket->root_cause_detail = null;
    $ticket->resolution_notes = null;
    $ticket->preventive_measure_specific = null;
    $ticket->preventive_measure_specific_due_date = null;
    $ticket->preventive_measure_systemic = null;
    $ticket->preventive_measure_systemic_due_date = null;

    // Set status back to in_progress (meaning Step 1 is done, Step 2 is active)
    $ticket->preventive_measure = 'in_progress';
    $ticket->pcar_analyzed_at = null;
    $ticket->pcar_analyzed_by = null;
    $ticket->pcar_closed_at = null;
    $ticket->pcar_closed_by = null;

    $ticket->save();
    echo 'TICKET_CLEARED';
} else {
    echo 'TICKET_NOT_FOUND';
}
