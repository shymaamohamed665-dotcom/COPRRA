@props([
    'title' => null,
    'subtitle' => null,
    'header' => null,
    'footer' => null,
    'padding' => 'p-6',
    'shadow' => 'shadow-sm',
    'rounded' => 'rounded-lg',
    'border' => 'border border-gray-200',
    'background' => 'bg-white',
])

@php
    $classes = implode(' ', [
        $background,
        $border,
        $rounded,
        $shadow,
        $padding,
    ]);
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($header || $title || $subtitle)
        <div class="border-b border-gray-200 pb-4 mb-4">
            @if($header)
                {{ $header }}
            @else
                @if($title)
                    <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
                @endif
            @endif
        </div>
    @endif
    
    <div class="card-body">
        {{ $slot }}
    </div>
    
    @if($footer)
        <div class="border-t border-gray-200 pt-4 mt-4">
            {{ $footer }}
        </div>
    @endif
</div>
