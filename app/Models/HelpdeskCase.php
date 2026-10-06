<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpdeskCase extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sla_due_at' => 'datetime',
        'assigned_at' => 'datetime',
        'analyzing_at' => 'datetime',
        'in_progress_at' => 'datetime',
        'resolved_at' => 'datetime',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'pcar_opened_at' => 'datetime',
        'pcar_analyzed_at' => 'datetime',
        'pcar_closed_at' => 'datetime',
    ];

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'ticket_no',
        'pcar_no',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'requester_name',
        'actual_user_name',
        'requester_email',
        'requester_phone',
        'machine_name',
        'machine_code',
        'is_machine_stopped',
        'company',
        'department',
        'location',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'escalated_to_team',
        'analysis_notes',
        'attachment_path',
        'resolution_notes',
        'why_1',
        'why_2',
        'why_3',
        'root_cause_category',
        'root_cause_detail',
        'preventive_measure',
        'preventive_measure_specific',
        'preventive_measure_systemic',
        'preventive_measure_specific_due_date',
        'preventive_measure_systemic_due_date',
        'sla_due_at',
        'analyzing_at',
        'in_progress_at',
        'resolved_at',
        'closed_at',
        'cancelled_at',
        'analyzing_by',
        'in_progress_by',
        'resolved_by',
        'approved_at',
        'approved_by',
        'requires_preventive_measure',
        'closed_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'rating',
        'feedback',
        'pcar_opened_at',
        'pcar_opened_by',
        'pcar_analyzed_at',
        'pcar_analyzed_by',
        'pcar_closed_at',
        'pcar_closed_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sla_due_at' => 'datetime',
            'assigned_at' => 'datetime',
            'analyzing_at' => 'datetime',
            'in_progress_at' => 'datetime',
            'resolved_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'requires_preventive_measure' => 'boolean',
            'analysis_notes' => 'array',
            'pcar_opened_at' => 'datetime',
            'pcar_analyzed_at' => 'datetime',
            'pcar_closed_at' => 'datetime',
            'preventive_measure_specific_due_date' => 'date',
            'preventive_measure_systemic_due_date' => 'date',
        ];
    }

    public function getPreventiveMeasureSpecificAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [['detail' => $value, 'due_date' => optional($this->preventive_measure_specific_due_date)->format('Y-m-d')]];
    }

    public function setPreventiveMeasureSpecificAttribute($value)
    {
        $this->attributes['preventive_measure_specific'] = is_array($value) ? json_encode(array_values($value), JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getPreventiveMeasureSystemicAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [['detail' => $value, 'due_date' => optional($this->preventive_measure_systemic_due_date)->format('Y-m-d')]];
    }

    public function setPreventiveMeasureSystemicAttribute($value)
    {
        $this->attributes['preventive_measure_systemic'] = is_array($value) ? json_encode(array_values($value), JSON_UNESCAPED_UNICODE) : $value;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function analyzingBy()
    {
        return $this->belongsTo(User::class, 'analyzing_by');
    }

    public function inProgressBy()
    {
        return $this->belongsTo(User::class, 'in_progress_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function pcarOpenedBy()
    {
        return $this->belongsTo(User::class, 'pcar_opened_by');
    }

    public function pcarAnalyzedBy()
    {
        return $this->belongsTo(User::class, 'pcar_analyzed_by');
    }

    public function pcarClosedBy()
    {
        return $this->belongsTo(User::class, 'pcar_closed_by');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'helpdesk_case_id');
    }

    public function parts()
    {
        return $this->hasMany(TicketPart::class, 'helpdesk_case_id');
    }

    /**
     * Filter cases query by criteria.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_no', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('requester_name', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('assigned_to', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status === 'active') {
                    $query->whereIn('status', ['assigned', 'analyzing', 'in_progress']);
                } elseif ($status === 'testing') {
                    $query->whereIn('status', ['resolved', 'approved']);
                } elseif ($status === 'not_closed') {
                    $query->whereNotIn('status', ['closed', 'cancelled']);
                } elseif ($status === 'completed') {
                    $query->whereIn('status', ['closed', 'cancelled']);
                } elseif ($status === 'pending') {
                    $query->whereIn('status', ['pending', 'assigned']);
                } elseif ($status === 'manager_review' || $status === 'task2_manager_review') {
                    $query->where('status', 'closed')
                        ->whereNull('requires_preventive_measure');
                } elseif ($status === 'task2_all') {
                    $query->where('status', 'closed');
                } elseif ($status === 'task2_assigned') {
                    $query->where('status', 'closed')
                        ->where('requires_preventive_measure', true)
                        ->where(function ($q) {
                            $q->whereNull('preventive_measure')
                                ->orWhere('preventive_measure', 'assigned');
                        });
                } elseif ($status === 'task2_in_progress') {
                    $query->where('status', 'closed')
                        ->where('requires_preventive_measure', true)
                        ->where('preventive_measure', 'in_progress');
                } elseif ($status === 'task2_pending_review') {
                    $query->where('status', 'closed')
                        ->where('requires_preventive_measure', true)
                        ->where('preventive_measure', 'pending_review');
                } elseif ($status === 'task2_completed') {
                    $query->where('status', 'closed')
                        ->where('requires_preventive_measure', true)
                        ->where('preventive_measure', 'done');
                } elseif ($status !== 'all') {
                    $query->where('status', $status);
                }
            })
            ->when($filters['priority'] ?? null, function ($query, $priority) {
                if ($priority !== 'all') {
                    $query->where('priority', $priority);
                }
            })
            ->when($filters['category'] ?? null, function ($query, $category) {
                if ($category !== 'all') {
                    $query->where('category', $category);
                }
            })
            ->when($filters['department'] ?? null, function ($query, $department) {
                if ($department !== 'all') {
                    $query->where('department', $department);
                }
            })
            ->when($filters['team'] ?? null, function ($query, $team) {
                if ($team !== 'all') {
                    $query->where('escalated_to_team', $team);
                }
            });
    }

    /**
     * Get human-readable Thai label for status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอดำเนินการ',
            'assigned' => 'มอบหมายแล้ว (รอรับงาน)',
            'analyzing' => 'กำลังสืบสภาพ และวิเคราะห์หาสาเหตุ',
            'in_progress' => 'กำลังดำเนินการแก้ไข',
            'resolved' => 'รอผู้แจ้งรับงาน',
            'approved' => 'รอหัวหน้าปิดใบงาน',
            'closed' => 'ปิดใบงาน',
            'cancelled' => 'ยกเลิกเคส',
            default => $this->status,
        };
    }

    public function getTask2StatusLabelAttribute(): ?string
    {
        if ($this->status !== 'closed') {
            return null;
        }

        if (is_null($this->requires_preventive_measure)) {
            return 'รอหัวหน้าตรวจสอบ';
        }

        if ($this->requires_preventive_measure) {
            return match ($this->preventive_measure) {
                null, 'assigned' => 'PCAR: รอดำเนินการ',
                'in_progress' => 'PCAR: กำลังดำเนินการ',
                'pending_review' => 'PCAR: รอตรวจสอบปิด',
                'done' => 'PCAR: ปิดมาตรการ',
                default => 'PCAR: ' . $this->preventive_measure,
            };
        }

        return null;
    }

    public function getTask2StatusColorAttribute(): ?string
    {
        if ($this->status !== 'closed') {
            return null;
        }

        if (is_null($this->requires_preventive_measure)) {
            return 'bg-orange-100 text-orange-700';
        }

        if ($this->requires_preventive_measure) {
            return match ($this->preventive_measure) {
                null, 'assigned' => 'bg-rose-100 text-rose-700',
                'in_progress' => 'bg-yellow-100 text-yellow-700',
                'pending_review' => 'bg-blue-100 text-blue-700',
                'done' => 'bg-teal-100 text-teal-700',
                default => 'bg-gray-100 text-gray-700',
            };
        }

        return null;
    }

    /**
     * Get human-readable Thai label for priority.
     */
    public function getPriorityLabelAttribute(): string
    {
        if ($this->status === 'pending') {
            return 'รอการประเมิน';
        }

        return match ($this->priority) {
            'urgent' => 'ด่วนที่สุด',
            'high' => 'สูง',
            'medium' => 'ปานกลาง',
            'low' => 'ทั่วไป/ต่ำ',
            default => $this->priority ?: 'รอการประเมิน',
        };
    }

    /**
     * Get human-readable Thai label for assigned team.
     */
    public function getAssignedTeamLabelAttribute(): string
    {
        if ($this->status === 'pending') {
            return 'รอการประเมิน';
        }

        return $this->escalated_to_team ? 'ทีม '.$this->escalated_to_team : 'Helpdesk (Tier 1)';
    }

    /**
     * Check if case is overdue according to SLA.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->status, ['resolved', 'closed', 'cancelled'], true)) {
            return false;
        }

        return $this->sla_due_at && $this->sla_due_at->isPast();
    }
}
