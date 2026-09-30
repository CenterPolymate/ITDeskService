<?php

use App\Models\HelpdeskCase;
use App\Models\User;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = User::where('role', 'manager')->first();
Auth::login($user);

$query = HelpdeskCase::filter(['status' => 'task2_manager_review']);
echo $query->toSql()."\n";
echo 'Bindings: '.json_encode($query->getBindings())."\n";
echo 'Count: '.$query->count()."\n";
