<?php

namespace App\Http\Controllers;

use App\Models\HelpdeskCase;
use App\Models\Sla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * Show the form for creating a new ticket.
     */
    public function create()
    {
        return view('tickets.create');
    }

    /**
     * Store a newly created ticket in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'category' => 'nullable|string|max:100',
            'description' => 'required|string|max:400',
            'location' => 'required|string|max:255',
            'requester_phone' => ['required', 'string', 'regex:/^0[0-9]{1,2}-?[0-9]{3}-?[0-9]{4}$/'],
            'attachments' => 'nullable|array|max:2',
            'attachments.*' => 'file|mimes:jpg,jpeg,png|max:5120', // 5MB max per file
        ], [
            'location.required' => 'กรุณาระบุสถานที่/จุดที่เกิดปัญหา',
            'requester_phone.required' => 'กรุณากรอกเบอร์โทรศัพท์ติดต่อกลับ',
            'requester_phone.regex' => 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง (เช่น 081-123-4567 หรือ 0811234567)',
            'attachments.max' => 'แนบรูปภาพได้สูงสุด 2 รูปเท่านั้น',
        ]);

        $user = Auth::user();

        // Generate Ticket Number: IT-YYYYMM-XXXX
        $prefix = 'IT-'.now()->format('Ym').'-';

        // Find the last ticket created in this month
        $lastTicket = HelpdeskCase::where('ticket_no', 'like', $prefix.'%')
            ->orderBy('ticket_no', 'desc')
            ->first();

        if ($lastTicket) {
            // Extract the last 4 digits and increment
            $lastNumber = (int) substr($lastTicket->ticket_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            // Start from 1 if no tickets exist this month
            $newNumber = 1;
        }

        $ticketNo = $prefix.str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        // Create Case
        $ticket = HelpdeskCase::create([
            'ticket_no' => $ticketNo,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category ?? 'Other',
            'priority' => 'medium', // Default priority for user submissions
            'status' => 'pending',
            'requester_name' => $user->name,
            'requester_email' => $user->email,
            'requester_phone' => $request->requester_phone,
            'user_id' => $user->id,
            'company' => $user->company,
            'department' => $user->department,
            'location' => $request->location,
        ]);

        // Handle File Upload (Max 2 files)
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $ticket->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // Send Notification
        // $user->notify(new \App\Notifications\TicketCreated($ticket)); // TBD: Wait for user approval for Email/Line

        return redirect()->route('dashboard')->with('success', "เปิดใบงานใหม่สำเร็จ! หมายเลข Ticket: {$ticketNo}");
    }

    /**
     * Display the specified ticket.
     */
    public function show($id)
    {
        $ticket = HelpdeskCase::with(['analyzingBy', 'inProgressBy', 'resolvedBy', 'closedBy', 'cancelledBy'])->findOrFail($id);

        // Check if user has permission to view
        $user = Auth::user();
        if ($user->role === 'user' && $ticket->requester_email !== $user->email) {
            abort(403, 'Unauthorized action.');
        }

        return view('tickets.show', compact('ticket'));
    }

    /**
     * Print the specified ticket report (P-CAR Form).
     */
    public function print($id)
    {
        $ticket = HelpdeskCase::with(['analyzingBy', 'inProgressBy', 'resolvedBy', 'closedBy', 'cancelledBy'])->findOrFail($id);

        // Check if user has permission to view
        $user = Auth::user();
        if ($user->role === 'user' && $ticket->requester_email !== $user->email) {
            abort(403, 'Unauthorized action.');
        }

        return view('tickets.print', compact('ticket'));
    }

    /**
     * Export the specified ticket report (P-CAR Form) to Excel.
     */
    public function exportExcel($id)
    {
        $ticket = HelpdeskCase::with(['analyzingBy', 'inProgressBy', 'resolvedBy', 'closedBy', 'cancelledBy'])->findOrFail($id);

        // Check if user has permission to view
        $user = Auth::user();
        if ($user->role === 'user' && $ticket->requester_email !== $user->email) {
            abort(403, 'Unauthorized action.');
        }

        $headers = [
            'Content-type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"PCAR_{$ticket->ticket_no}.xls\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->view('tickets.export-excel', compact('ticket'))->withHeaders($headers);
    }

    /**
     * Update the specified ticket (Triage & Escalate).
     */
    public function assign(Request $request, $id)
    {
        $ticket = HelpdeskCase::findOrFail($id);

        if ($ticket->status === 'closed') {
            return redirect()->back()->withErrors('ไม่สามารถดำเนินการได้ เนื่องจากใบงานถูกปิดแล้ว');
        }

        if (! in_array($ticket->status, ['pending', 'assigned']) && ! is_null($ticket->escalated_to_team)) {
            return redirect()->back()->withErrors('ไม่สามารถแก้ไขการมอบหมายงานได้ เนื่องจากทีมได้รับงานไปแล้ว');
        }

        $request->validate([
            'priority' => 'required|in:urgent,high,medium,low',
            'escalated_to_team' => 'required|string',
        ]);

        // Default status update based on team
        // If escalated to a team, status becomes 'assigned'
        $status = 'assigned';
        if ($request->escalated_to_team === 'None') {
            // If helpdesk keeps it
            $request->merge(['escalated_to_team' => null]);
        }

        $updateData = [
            'priority' => $request->priority,
            'escalated_to_team' => $request->escalated_to_team === 'None' ? null : $request->escalated_to_team,
            'status' => $status,
            'assigned_at' => now(),
            'assigned_by' => auth()->id(),
        ];

        // Determine SLA based on priority and company from database
        $slaConfig = Sla::where('company', $ticket->company)
            ->where('priority', $request->priority)
            ->first();

        // Fallback to a default if not found
        if (! $slaConfig) {
            $slaConfig = Sla::where('priority', $request->priority)->first();
        }
        $slaHours = $slaConfig ? $slaConfig->hours : 24;

        if (is_null($ticket->sla_due_at)) {
            $updateData['sla_due_at'] = now()->addHours($slaHours);
        }

        if ($status === 'in_progress') {
            $updateData['in_progress_at'] = now();
            $updateData['in_progress_by'] = Auth::id();
            $updateData['analyzing_at'] = now();
            $updateData['analyzing_by'] = Auth::id();
        } else {
            $updateData['analyzing_at'] = null;
            $updateData['analyzing_by'] = null;
            $updateData['in_progress_at'] = null;
            $updateData['in_progress_by'] = null;
            $updateData['resolved_at'] = null;
            $updateData['resolved_by'] = null;
            $updateData['analysis_notes'] = null;
            $updateData['resolution_notes'] = null;
        }

        $ticket->update($updateData);

        return redirect()->route('tickets.show', $id)->with('success', 'ประเมินและมอบหมายงานเรียบร้อยแล้ว');
    }

    /**
     * Update the ticket status by assigned team (Step 3 & 4).
     */
    public function updateStatus(Request $request, $id)
    {
        $ticket = HelpdeskCase::findOrFail($id);

        // Allow manager to 'close' (review) and allow IT team to submit 'preventive_action' even if closed
        if ($ticket->status === 'closed' && ! ($request->action === 'close' && Auth::user()->role === 'manager') && ! in_array($request->action, ['start_preventive_measure', 'preventive_action', 'close_preventive_measure'])) {
            return redirect()->back()->withErrors('ไม่สามารถดำเนินการได้ เนื่องจากใบงานถูกปิดแล้ว');
        }

        $request->validate([
            'action' => 'required|in:start_analyzing,start_progress,resolve,update_notes,update_analysis,accept_resolution,close,cancel,start_preventive_measure,preventive_action,close_preventive_measure',
            'resolution_notes' => 'required_if:action,resolve|nullable|string',
            'analysis_notes' => 'nullable|array',
            'analysis_notes.root_cause' => 'required_with:analysis_notes|nullable|string',
            'requires_preventive_measure' => 'required_if:action,close|nullable|boolean',
            'cancellation_reason' => 'required_if:action,cancel|nullable|string',
        ]);

        if ($request->action === 'start_analyzing') {
            $ticket->update([
                'status' => 'analyzing',
                'analyzing_at' => now(),
                'analyzing_by' => Auth::id(),
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'เริ่มดำเนินการสืบสภาพแล้ว');
        } elseif ($request->action === 'update_analysis') {
            $notes = $request->analysis_notes;

            $updateData = [
                'analysis_notes' => $notes,
                'analyzing_by' => Auth::id(),
            ];

            // If root cause is filled, automatically transition to in_progress
            if (! empty($notes['root_cause'])) {
                $updateData['status'] = 'in_progress';
                $updateData['in_progress_at'] = now();
                $updateData['in_progress_by'] = Auth::id();
                $message = 'บันทึกสาเหตุรากเหง้าเรียบร้อย ระบบได้เริ่มนับเวลาการแก้ไขแล้ว';
            } else {
                $message = 'บันทึกข้อมูลสืบสภาพเบื้องต้นแล้ว (กรุณาระบุสาเหตุรากเหง้าเพื่อเริ่มการแก้ไข)';
            }

            $ticket->update($updateData);

            return redirect()->route('tickets.show', $id)->with('success', $message);
        } elseif ($request->action === 'resolve') {
            $updateData = [
                'status' => 'resolved',
                'resolution_notes' => $request->resolution_notes,
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ];

            if (is_null($ticket->in_progress_at)) {
                $updateData['in_progress_at'] = now();
                $updateData['in_progress_by'] = Auth::id();
            }
            if (is_null($ticket->analyzing_at) && is_null($ticket->escalated_to_team)) {
                $updateData['analyzing_at'] = now();
                $updateData['analyzing_by'] = Auth::id();
            }

            $ticket->update($updateData);

            return redirect()->route('tickets.show', $id)->with('success', 'บันทึกการแก้ไขและส่งเพื่อรอผู้แจ้งรับงานเรียบร้อยแล้ว');
        } elseif ($request->action === 'update_notes') {
            $ticket->update([
                'resolution_notes' => $request->resolution_notes,
                'analysis_notes' => $request->analysis_notes ?? $ticket->analysis_notes,
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'อัปเดตข้อมูลสำเร็จแล้ว');
        } elseif ($request->action === 'accept_resolution') {
            $isRequester = Auth::user()->email === $ticket->requester_email;
            $isHelpdesk = Auth::user()->role === 'helpdesk';

            if (! $isRequester && $isHelpdesk) {
                if ($ticket->resolved_at && now()->lessThan($ticket->resolved_at->copy()->addMinutes(5))) {
                    return back()->with('error', 'Helpdesk สามารถกดยืนยันแทนผู้แจ้งได้เมื่อเวลาผ่านไปแล้ว 5 นาทีเท่านั้น');
                }
            }

            $updateData = [
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ];

            // Only requester can submit rating
            if ($isRequester && $request->has('rating')) {
                $updateData['rating'] = $request->rating;
                $updateData['feedback'] = $request->feedback;
            }

            $updateData['status'] = 'closed';
            $updateData['closed_at'] = now();
            $updateData['closed_by'] = Auth::id();
            $msg = 'รับทราบการแก้ไขและปิดใบงานเรียบร้อยแล้ว';

            $ticket->update($updateData);

            return redirect()->route('tickets.show', $id)->with('success', $msg);
        } elseif ($request->action === 'cancel') {
            $isRequester = Auth::user()->email === $ticket->requester_email;
            $isHelpdesk = Auth::user()->role === 'helpdesk';
            $isManager = str_contains(Auth::user()->role, 'manager');

            if (! $isRequester && ! $isHelpdesk && ! $isManager) {
                return back()->with('error', 'คุณไม่มีสิทธิ์ยกเลิกใบงานนี้');
            }

            if (in_array($ticket->status, ['resolved', 'approved', 'closed', 'cancelled'])) {
                return back()->with('error', 'ไม่สามารถยกเลิกเคสในสถานะปัจจุบันได้');
            }

            $ticket->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => $request->cancellation_reason,
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'ยกเลิกเคสเรียบร้อยแล้ว');
        } elseif ($request->action === 'close') {
            $updateData = [
                'requires_preventive_measure' => $request->requires_preventive_measure,
            ];

            if ($request->requires_preventive_measure == 1 && $request->filled('escalated_to_team')) {
                $updateData['escalated_to_team'] = $request->escalated_to_team;
                $updateData['preventive_measure'] = 'assigned';
            } elseif ($request->requires_preventive_measure == 0) {
                $updateData['preventive_measure'] = null;
            }

            $ticket->update($updateData);

            return redirect()->route('tickets.show', $id)->with('success', 'บันทึกการตรวจสอบและส่งต่องานเรียบร้อยแล้ว');
        } elseif ($request->action === 'start_preventive_measure') {
            $ticket->update([
                'preventive_measure' => 'in_progress',
                'pcar_opened_at' => now(),
                'pcar_opened_by' => Auth::id(),
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'เริ่มดำเนินการสืบสภาพ (Task 2) เรียบร้อยแล้ว');
        } elseif ($request->action === 'preventive_action') {
            $ticket->update([
                'why_1' => $request->why_1,
                'why_2' => $request->why_2,
                'why_3' => $request->why_3,
                'root_cause_category' => $request->root_cause_category,
                'root_cause_detail' => $request->root_cause_detail,
                'resolution_notes' => $request->resolution_notes,
                'preventive_measure_specific' => $request->preventive_measure_specific,
                'preventive_measure_specific_due_date' => $request->preventive_measure_specific_due_date,
                'preventive_measure_systemic' => $request->preventive_measure_systemic,
                'preventive_measure_systemic_due_date' => $request->preventive_measure_systemic_due_date,
                'preventive_measure' => 'pending_review',
                'pcar_analyzed_at' => now(),
                'pcar_analyzed_by' => Auth::id(),
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'บันทึกข้อมูล Task 2 แล้ว รอหัวหน้าตรวจสอบและปิดมาตรการ');
        } elseif ($request->action === 'close_preventive_measure') {
            $ticket->update([
                'preventive_measure' => 'done',
                'pcar_closed_at' => now(),
                'pcar_closed_by' => Auth::id(),
            ]);

            return redirect()->route('tickets.show', $id)->with('success', 'ปิดมาตรการป้องกันเรียบร้อยแล้ว');
        }

        return back();
    }
}
