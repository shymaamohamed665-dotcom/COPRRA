@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
    'loading' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200';
    
    $variantClasses = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'warning' => 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
        'info' => 'bg-cyan-600 text-white hover:bg-cyan-700 focus:ring-cyan-500',
        'outline' => 'border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-blue-500',
        'ghost' => 'text-gray-700 hover:bg-gray-100 focus:ring-gray-500',
    ];
    
    $sizeClasses = [
        'xs' => 'px-2.5 py-1.5 text-xs',
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-4 py-2 text-base',
        'xl' => 'px-6 py-3 text-base',
    ];
    
    $disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed' : '';
    $loadingClasses = $loading ? 'opacity-75 cursor-wait' : '';
    
    $classes = implode(' ', [
        $baseClasses,
        $variantClasses[$variant] ?? $variantClasses['primary'],
        $sizeClasses[$size] ?? $sizeClasses['md'],
        $disabledClasses,
        $loadingClasses,
    ]);
@endphp

<button 
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @if($disabled) disabled @endif
>
    @if($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif
    
    {{ $slot }}
</button>
