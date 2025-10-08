@props([
    'id' => null,
    'title' => null,
    'size' => 'md',
    'closable' => true,
    'backdrop' => true,
])

@php
    $modalId = $id ?? 'modal-' . uniqid();
    $sizeClasses = [
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        '2xl' => 'max-w-6xl',
        'full' => 'max-w-full mx-4',
    ];
@endphp

<div
    id="{{ $modalId }}"
    class="fixed inset-0 z-50 overflow-y-auto hidden"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
    x-data="{ show: false }"
    x-show="show"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @if($backdrop) @click="show = false" @endif
>
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        @if($backdrop)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        @endif

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle {{ $sizeClasses[$size] ?? $sizeClasses['md'] }} sm:w-full"
            @click.stop
        >
            @if($title || $closable)
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between">
                        @if($title)
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                {{ $title }}
                            </h3>
                        @endif

                        @if($closable)
                            <button
                                type="button"
                                class="bg-white rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                @click="show = false"
                                aria-label="Close modal"
                            >
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                {{ $slot }}
            </div>

            @if(isset($footer))
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    // Modal control functions
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.querySelector('[x-data]').__x.$data.show = true;
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.querySelector('[x-data]').__x.$data.show = false;
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    };
</script>
