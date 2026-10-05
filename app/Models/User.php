<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'company', 'email', 'password', 'role', 'is_active', 'department', 'phone', 'line_user_id', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is a helpdesk.
     */
    public function isHelpdesk(): bool
    {
        return $this->role === 'helpdesk';
    }

    /**
     * Check if user is a manager.
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if user has any historical records (cases, comments, audit logs).
     * Used to determine if the user can be safely hard-deleted.
     */
    public function hasHistory(): bool
    {
        $hasCases = HelpdeskCase::where('user_id', $this->id)
            ->orWhere('analyzing_by', $this->id)
            ->orWhere('in_progress_by', $this->id)
            ->orWhere('resolved_by', $this->id)
            ->orWhere('assigned_by', $this->id)
            ->orWhere('approved_by', $this->id)
            ->orWhere('closed_by', $this->id)
            ->orWhere('cancelled_by', $this->id)
            ->orWhere('pcar_opened_by', $this->id)
            ->orWhere('pcar_analyzed_by', $this->id)
            ->orWhere('pcar_closed_by', $this->id)
            ->exists();

        if ($hasCases) {
            return true;
        }

        if (TicketComment::where('user_id', $this->id)->exists()) {
            return true;
        }

        if (AuditLog::where('user_id', $this->id)->exists()) {
            return true;
        }

        return false;
    }
}
