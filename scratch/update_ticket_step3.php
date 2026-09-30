<?php

use App\Models\HelpdeskCase;
use App\Models\User;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = User::where('name', 'like', '%ณัฏฐนันท์%')->first(); // Manager
if ($user) {
    $ticket = HelpdeskCase::where('ticket_no', 'IT-20260924-1206')->first();
    if ($ticket) {
        $ticket->preventive_measure = 'done';
        $ticket->pcar_closed_at = '2026-09-24 21:45:00';
        $ticket->pcar_closed_by = $user->id;
        $ticket->save();
        echo 'TICKET_UPDATED';
    } else {
        echo 'TICKET_NOT_FOUND';
    }
} else {
    echo 'USER_NOT_FOUND';
}
