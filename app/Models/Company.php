<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'short_name', 'is_active', 'email_domains', 'sla_type'];

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
