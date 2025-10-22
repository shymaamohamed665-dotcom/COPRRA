@props([
    'type' => 'text',
    'label' => null,
    'error' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'placeholder' => null,
    'size' => 'md',
    'ariaLabel' => null,
    'ariaDescribedBy' => null,
    'ariaInvalid' => null,
])

@php
    $baseClasses = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm';
    
    $sizeClasses = [
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-3 py-2 text-sm',
        'lg' => 'px-4 py-3 text-base',
    ];
    
    $errorClasses = $error ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '';
    $disabledClasses = $disabled ? 'bg-gray-50 cursor-not-allowed' : '';
    $readonlyClasses = $readonly ? 'bg-gray-50' : '';
    
    $classes = implode(' ', [
        $baseClasses,
        $sizeClasses[$size] ?? $sizeClasses['md'],
        $errorClasses,
        $disabledClasses,
        $readonlyClasses,
    ]);
@endphp

<div class="space-y-1">
    @if($label)
        <label class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <input
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        @if($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
        @if($ariaDescribedBy) aria-describedby="{{ $ariaDescribedBy }}" @endif
        @if($ariaInvalid !== null) aria-invalid="{{ $ariaInvalid ? 'true' : 'false' }}" @endif
        @if($error) aria-invalid="true" @endif
    />
    
    @if($help && !$error)
        <p class="text-sm text-gray-500">{{ $help }}</p>
    @endif
    
    @if($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
