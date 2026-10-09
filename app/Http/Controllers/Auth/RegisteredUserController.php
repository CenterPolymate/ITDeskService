<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        $companies = Company::with('departments')->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Auth/Register', [
            'companies' => $companies,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'exists:companies,name'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class,
                function ($attribute, $value, $fail) use ($request) {
                    $company = Company::where('name', $request->company)->first();
                    if ($company && ! empty($company->email_domains)) {
                        $allowedDomains = array_map('trim', explode(',', $company->email_domains));

                        $emailParts = explode('@', $value);
                        if (count($emailParts) !== 2) {
                            return $fail('รูปแบบอีเมลไม่ถูกต้อง');
                        }
                        $emailDomain = '@'.strtolower($emailParts[1]);

                        $domainMatched = false;
                        foreach ($allowedDomains as $allowed) {
                            if (strtolower($allowed) === $emailDomain) {
                                $domainMatched = true;
                                break;
                            }
                        }

                        if (! $domainMatched) {
                            $fail("อีเมลโดเมน $emailDomain ไม่ได้รับอนุญาตให้ใช้สมัครสมาชิกสำหรับบริษัทนี้");
                        }
                    }
                },
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'department' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'company' => $request->company,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'department' => $request->department,
            'phone' => $request->phone,
            'role' => 'user', // Default role for new registrations
        ]);

        event(new Registered($user));

        Auth::login($user);

        return Inertia::location(route('dashboard', absolute: false));
    }
}
