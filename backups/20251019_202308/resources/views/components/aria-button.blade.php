@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
    'loading' => false,
    'icon' => null,
    'iconPosition' => 'left',
    'ariaLabel' => null,
    'ariaDescribedBy' => null,
    'ariaExpanded' => null,
    'ariaControls' => null,
    'ariaPressed' => null,
    'ariaCurrent' => null,
    'role' => 'button',
    'tabindex' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed';
    
    $variantClasses = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
        'secondary' => 'bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        'warning' => 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
        'error' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'ghost' => 'text-gray-700 hover:bg-gray-100 focus:ring-gray-500',
        'link' => 'text-primary-600 hover:text-primary-500 focus:ring-primary-500 underline',
    ];
    
    $sizeClasses = [
        'xs' => 'px-2 py-1 text-xs',
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
        'xl' => 'px-8 py-4 text-lg',
    ];
    
    $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @if($disabled) disabled @endif
    @if($loading) disabled @endif
    @if($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
    @if($ariaDescribedBy) aria-describedby="{{ $ariaDescribedBy }}" @endif
    @if($ariaExpanded !== null) aria-expanded="{{ $ariaExpanded ? 'true' : 'false' }}" @endif
    @if($ariaControls) aria-controls="{{ $ariaControls }}" @endif
    @if($ariaPressed !== null) aria-pressed="{{ $ariaPressed ? 'true' : 'false' }}" @endif
    @if($ariaCurrent) aria-current="{{ $ariaCurrent }}" @endif
    @if($role) role="{{ $role }}" @endif
    @if($tabindex !== null) tabindex="{{ $tabindex }}" @endif
    @if($loading) aria-busy="true" @endif
>
    @if($loading)
        <x-icon name="heroicon-o-arrow-path" class="animate-spin h-4 w-4 mr-2" />
    @elseif($icon && $iconPosition === 'left')
        <x-icon :name="$icon" class="h-4 w-4 mr-2" />
    @endif
    
    <span @if($loading) class="sr-only" @endif>
        {{ $slot }}
    </span>
    
    @if($icon && $iconPosition === 'right')
        <x-icon :name="$icon" class="h-4 w-4 ml-2" />
    @endif
</button>
