<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Dashboard Training Management
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Monitoring dan analisis data pelatihan Inixindo
            </p>
        </div>

        {{-- Widgets Container --}}
        @livewire(\Filament\Widgets\WidgetsManager::class, ['widgets' => $this->getVisibleWidgets()])
    </div>
</x-filament-panels::page>
