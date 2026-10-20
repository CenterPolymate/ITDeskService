<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'company', 'email', 'password', 'role', 'is_active', 'department', 'phone', 'line_user_id', 'last_login_at', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
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
     * Manager roles list.
     */
    public static function managerRoles(): array
    {
        return ['manager', 'manager_software', 'manager_network_hardware'];
    }

    /**
     * Check if user is a manager.
     */
    public function isManager(): bool
    {
        return in_array($this->role, self::managerRoles(), true);
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdministrator(): bool
    {
        return $this->role === 'administrator';
    }

    /**
     * Check if user is a technician / Tier 2 specialist.
     */
    public function isTechnician(): bool
    {
        return in_array($this->role, ['technician', 'team_hardware', 'team_network', 'team_software'], true)
            || $this->teams()->exists();
    }

    /**
     * Teams supervised by this user as a Manager.
     */
    public function managedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'manager_id');
    }

    /**
     * Teams this user belongs to as a member/technician.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('role_in_team')
            ->withTimestamps();
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
