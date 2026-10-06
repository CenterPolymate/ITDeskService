<?php

namespace App\Http\Controllers;

use App\Models\HelpdeskCase;
use App\Models\TicketComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketCommentController extends Controller
{
    public function store(Request $request, $id)
    {
        $ticket = HelpdeskCase::findOrFail($id);

        $request->validate([
            'message' => 'required_without:attachment|nullable|string',
            'attachment' => 'nullable|image|max:5120', // Images only, max 5MB
        ]);

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('comments', 'public');
        }

        TicketComment::create([
            'helpdesk_case_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'attachment_path' => $path,
        ]);

        return redirect()->back()->with('success', 'เพิ่มข้อความพูดคุยเรียบร้อยแล้ว');
    }
}
