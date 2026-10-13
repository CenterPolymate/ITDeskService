<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ImpersonateController extends Controller
{
    public function impersonate(User $user)
    {
        if (Auth::user()->role !== 'administrator') {
            abort(403);
        }

        if ($user->id === Auth::id() || $user->role === 'administrator') {
            return redirect()->back()->with('error', 'ไม่สามารถจำลองเป็นผู้ใช้งานนี้ได้');
        }

        session()->put('impersonated_by', Auth::id());
        Auth::login($user);

        session()->flash('success', 'เข้าสู่ระบบในฐานะ '.$user->name.' สำเร็จ');

        return Inertia::location(route('dashboard'));
    }

    public function leave()
    {
        if (! session()->has('impersonated_by')) {
            abort(403);
        }

        $adminId = session()->get('impersonated_by');
        Auth::loginUsingId($adminId);
        session()->forget('impersonated_by');

        session()->flash('success', 'กลับสู่บัญชีผู้ดูแลระบบสำเร็จ');

        return Inertia::location(route('dashboard'));
    }
}
