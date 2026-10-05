<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

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

        return redirect()->route('dashboard')->with('success', 'เข้าสู่ระบบในฐานะ '.$user->name.' สำเร็จ');
    }

    public function leave()
    {
        if (! session()->has('impersonated_by')) {
            abort(403);
        }

        $adminId = session()->pull('impersonated_by');
        Auth::loginUsingId($adminId);

        return redirect()->route('dashboard')->with('success', 'กลับสู่บัญชีผู้ดูแลระบบสำเร็จ');
    }
}
