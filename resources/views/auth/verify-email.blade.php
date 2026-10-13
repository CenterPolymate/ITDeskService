<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('ขอบคุณที่สมัครสมาชิกเข้าใช้งานระบบ ITDeskService! ก่อนเริ่มต้นใช้งาน กรุณายืนยันที่อยู่อีเมลของคุณ โดยคลิกที่ลิงก์ที่เราได้ส่งไปยังอีเมลของคุณ หากคุณไม่ได้รับอีเมล สามารถกดปุ่มส่งใหม่อีกครั้งได้') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('ระบบได้ส่งลิงก์ยืนยันตัวตนใหม่ไปยังที่อยู่อีเมลของคุณเรียบร้อยแล้ว') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('ส่งอีเมลยืนยันอีกครั้ง') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('ออกจากระบบ') }}
            </button>
        </form>
    </div>
</x-guest-layout>
