<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketPart extends Model
{
    protected $fillable = [
        'helpdesk_case_id',
        'part_name',
        'quantity',
        'unit',
    ];

    public function ticket()
    {
        return $this->belongsTo(HelpdeskCase::class, 'helpdesk_case_id');
    }
}
