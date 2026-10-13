<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'short_name', 'is_active', 'email_domains', 'sla_type'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Check if company has any historical records (users, tickets).
     * Used to determine if the company can be safely hard-deleted.
     */
    public function hasHistory(): bool
    {
        $hasUsers = User::where('company', $this->name)->exists();
        $hasCases = HelpdeskCase::where('company', $this->name)->exists();

        return $hasUsers || $hasCases;
    }
}
