@extends('layouts.app')

@section('title', 'اتصل بنا')

@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">اتصل بنا</h1>
            <p class="text-lg text-gray-600 mb-12">نحن هنا لمساعدتك. تواصل معنا في أي وقت.</p>
        </div>

        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">معلومات الاتصال</h2>
                
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">البريد الإلكتروني</h3>
                        <p class="text-gray-600">info@coprra.com</p>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">الهاتف</h3>
                        <p class="text-gray-600">+966 50 123 4567</p>
                    </div>
                </div>
                
                <div class="mt-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">العنوان</h3>
                    <p class="text-gray-600">الرياض، المملكة العربية السعودية</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
