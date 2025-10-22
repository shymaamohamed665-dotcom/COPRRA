@props([
    'type' => 'info',
    'dismissible' => false,
    'icon' => null,
    'title' => null,
    'message' => null,
    'actions' => null
])

@php
    $alertClasses = [
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'danger' => 'bg-red-50 border-red-200 text-red-800'
    ];

    $iconClasses = [
        'info' => 'text-blue-400',
        'success' => 'text-green-400',
        'warning' => 'text-yellow-400',
        'error' => 'text-red-400',
        'danger' => 'text-red-400'
    ];

    $defaultIcons = [
        'info' => 'heroicon-o-information-circle',
        'success' => 'heroicon-o-check-circle',
        'warning' => 'heroicon-o-exclamation-triangle',
        'error' => 'heroicon-o-x-circle',
        'danger' => 'heroicon-o-x-circle'
    ];

    $alertClass = $alertClasses[$type] ?? $alertClasses['info'];
    $iconClass = $iconClasses[$type] ?? $iconClasses['info'];
    $iconName = $icon ?? $defaultIcons[$type] ?? $defaultIcons['info'];
@endphp

<div 
    {{ $attributes->merge(['class' => "rounded-md border p-4 {$alertClass}"]) }}
    role="alert"
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
>
    <div class="flex">
        @if($iconName)
            <div class="flex-shrink-0">
                <x-icon 
                    :name="$iconName" 
                    class="h-5 w-5 {{ $iconClass }}"
                />
            </div>
        @endif

        <div class="ml-3 flex-1">
            @if($title)
                <h3 class="text-sm font-medium">
                    {{ $title }}
                </h3>
            @endif

            @if($message)
                <div class="mt-2 text-sm">
                    <p>{{ $message }}</p>
                </div>
            @endif

            @if($slot->isNotEmpty())
                <div class="mt-2 text-sm">
                    {{ $slot }}
                </div>
            @endif

            @if($actions)
                <div class="mt-4">
                    <div class="-mx-2 -my-1.5 flex">
                        {{ $actions }}
                    </div>
                </div>
            @endif
        </div>

        @if($dismissible)
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button 
                        type="button"
                        class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $iconClass }} hover:bg-opacity-20"
                        @click="show = false"
                        aria-label="إغلاق"
                    >
                        <x-icon name="heroicon-o-x-mark" class="h-5 w-5" />
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
