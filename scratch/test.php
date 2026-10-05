<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$company = App\Models\Company::create(['name' => 'TCAI3', 'short_name' => 'T', 'is_active' => 1, 'email_domains' => '@t3.com']);
$sla = App\Models\Sla::create(['company' => 'TCAI3', 'priority' => 'urgent', 'hours' => 2, 'name_th' => 'ด่วนที่สุด']);
echo "SLA Created with ID: " . $sla->id . "\n";
