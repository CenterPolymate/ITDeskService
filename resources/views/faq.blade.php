<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ศูนย์ช่วยเหลือเบื้องต้น (FAQ & Knowledge Base)') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-gray-900" x-data="{ active: null }">
                    <div class="mb-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-blue-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-2xl font-bold text-gray-800">คำถามที่พบบ่อยและวิธีแก้ปัญหา</h3>
                        <p class="text-gray-500 mt-2">ลองทำตามวิธีเหล่านี้เบื้องต้น อาจจะช่วยแก้ปัญหาของคุณได้ทันทีโดยไม่ต้องรอคิวแจ้งซ่อม</p>
                    </div>

                    <div class="space-y-8">
                        @forelse ($faqs as $category => $categoryFaqs)
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-4">{{ $category }}</h3>
                                <div class="space-y-4">
                                    @foreach ($categoryFaqs as $faq)
                                        <div class="border border-gray-200 rounded-lg bg-white overflow-hidden" x-data="{ open: false }">
                                            <button @click="open = !open" class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none hover:bg-gray-50 transition-colors">
                                                <span class="font-semibold text-gray-800 flex items-center">
                                                    {{ $faq->question }}
                                                </span>
                                                <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </button>
                                            <div x-show="open" x-collapse class="px-6 pb-4 pt-2 text-gray-600 border-t border-gray-100 prose max-w-none">
                                                {!! $faq->answer !!}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-8">
                                ยังไม่มีข้อมูลศูนย์ช่วยเหลือเบื้องต้นในขณะนี้
                            </div>
                        @endforelse
                    </div>
                    
                    <div class="mt-12 text-center bg-gray-50 p-6 rounded-lg">
                        <p class="text-gray-600 mb-4">ยังไม่พบวิธีแก้ปัญหาใช่หรือไม่?</p>
                        <a href="{{ route('tickets.create') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            เปิดใบงานแจ้งซ่อมใหม่ (New Ticket)
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
