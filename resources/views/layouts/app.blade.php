<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="!px-0 !pt-0 !pb-24 lg:!pb-0">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
