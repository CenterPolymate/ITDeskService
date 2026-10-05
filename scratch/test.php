<?php

use App\Models\Company;
use App\Models\Sla;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$company = Company::create(['name' => 'TCAI3', 'short_name' => 'T', 'is_active' => 1, 'email_domains' => '@t3.com']);
$sla = Sla::create(['company' => 'TCAI3', 'priority' => 'urgent', 'hours' => 2, 'name_th' => 'ด่วนที่สุด']);
echo 'SLA Created with ID: '.$sla->id."\n";
