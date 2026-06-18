<?php

use App\Enums\TirePosition;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My tires')] class extends Component
{
    public string $productId = '';

    public string $position = 'REAR';

    public string $mountedAt = '';

    public int $mountedOdometerKm = 0;

    public function mount(): void
    {
        $this->mountedAt = now()->toDateString();
    }

    /**
     * The rider's mounted tires (front + rear).
     *
     * @return Collection<int, \App\Models\UserTire>
     */
    #[Computed]
    public function tires(): Collection
    {
        return auth()->user()->tires()->with('product')->orderByDesc('is_active')->get();
    }

    /**
     * The Michelin catalogue to choose from.
     *
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::orderBy('web_range_name')->get();
    }

    /**
     * Mount a tire at a position (one active tire per position — front/rear).
     */
    public function addTire(): void
    {
        $this->validate([
            'productId' => ['required', Rule::exists('products', 'id')],
            'position' => ['required', Rule::enum(TirePosition::class)],
            'mountedAt' => ['required', 'date'],
            'mountedOdometerKm' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        auth()->user()->tires()->updateOrCreate(
            ['position' => $this->position],
            [
                'product_id' => $this->productId,
                'mounted_at' => $this->mountedAt,
                'mounted_odometer_km' => $this->mountedOdometerKm,
                'wear_percent' => 0,
                'is_active' => true,
            ],
        );

        unset($this->tires);
        $this->reset('productId');
    }

    /**
     * Remove a mounted tire.
     */
    public function removeTire(int $tireId): void
    {
        auth()->user()->tires()->whereKey($tireId)->delete();

        unset($this->tires);
    }
}; ?>

<section class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <style>
        @keyframes mt-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        .mt-rise { animation: mt-rise .55s cubic-bezier(.16,1,.3,1) both; }
        .mt-field { width: 100%; border-radius: 0.75rem; border: 1px solid var(--color-zinc-300); background: var(--color-blanc); padding: 0.625rem 0.875rem; font-size: 0.875rem; font-weight: 500; color: var(--color-zinc-800); transition: border-color .15s, box-shadow .15s; }
        .mt-field:focus { outline: none; border-color: var(--color-michelin-blue); box-shadow: 0 0 0 3px color-mix(in oklab, var(--color-michelin-blue), transparent 75%); }
        .dark .mt-field { border-color: var(--color-zinc-700); background: var(--color-zinc-900); color: var(--color-zinc-100); }
    </style>

    {{-- HEADER --}}
    <header class="mt-rise">
        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-michelin-blue dark:text-michelin-blue-light">{{ __('Garage') }}</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-zinc-900 dark:text-white">{{ __('My tires') }}</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Declare the Michelin tires mounted on your bike to track their wear.') }}</p>
    </header>

    {{-- LISTE DES PNEUS --}}
    @if ($this->tires->isNotEmpty())
        <div class="flex flex-col gap-3" data-test="tires-table">
            @foreach ($this->tires as $tire)
                @php
                    $w = (float) ($tire->wear_percent ?? 0);
                    $tone = $w >= 80 ? '#b71c1c' : ($w >= 50 ? '#f9a825' : '#27509b');
                    $isFront = $tire->position === TirePosition::Front;
                @endphp
                <div class="mt-rise group relative flex items-center gap-4 overflow-hidden rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900"
                     style="animation-delay: {{ min(0.3, $loop->index * 0.05) }}s" wire:key="tire-{{ $tire->id }}" data-test="tire-row">
                    <span class="absolute inset-y-0 left-0 w-1.5" style="background: {{ $tone }}"></span>

                    <div class="flex min-w-0 flex-1 flex-col pl-1.5">
                        <span class="truncate text-sm font-black tracking-tight text-zinc-900 dark:text-white">{{ $tire->product->web_range_name }}</span>
                        <span class="mt-1 flex flex-wrap items-center gap-2 text-[11px] font-bold text-zinc-400">
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 uppercase tracking-wider text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $isFront ? __('Front') : __('Rear') }}</span>
                            @unless ($tire->is_active)
                                <span class="rounded-full bg-zinc-200 px-2 py-0.5 uppercase tracking-wider text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400" data-test="tire-retired">{{ __('Retired') }}</span>
                            @endunless
                            <span>{{ $tire->mounted_at?->translatedFormat('j M Y') ?? '—' }}</span>
                        </span>
                    </div>

                    <span class="shrink-0 text-lg font-black tabular-nums" style="color: {{ $tone }}">{{ number_format($w, 0) }}%</span>

                    <div class="flex shrink-0 items-center gap-1">
                        <a href="{{ route('tires.show', $tire) }}" wire:navigate title="{{ __('Tire detail') }}"
                           class="grid size-9 place-items-center rounded-lg text-zinc-400 transition hover:bg-michelin-blue/10 hover:text-michelin-blue" data-test="tire-detail-{{ $tire->id }}">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                        </a>
                        <button type="button" wire:click="removeTire({{ $tire->id }})" title="{{ __('Remove') }}"
                                class="grid size-9 place-items-center rounded-lg text-zinc-400 transition hover:bg-michelin-danger/10 hover:text-michelin-danger" data-test="tire-remove-{{ $tire->id }}">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="mt-rise flex flex-col items-center gap-2 rounded-2xl border border-dashed border-zinc-300 px-6 py-10 text-center dark:border-zinc-700" data-test="tires-empty">
            <svg class="size-9 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="3.2" /></svg>
            <h2 class="text-base font-black text-zinc-800 dark:text-zinc-100">{{ __('No tire registered yet') }}</h2>
            <p class="text-sm text-zinc-500">{{ __('Add your first tire below.') }}</p>
        </div>
    @endif

    {{-- FORMULAIRE D'AJOUT --}}
    <form wire:submit="addTire" class="mt-rise flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" style="animation-delay:.1s" data-test="add-tire-form">
        <h2 class="flex items-center gap-2 text-sm font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-100">
            <span class="inline-block h-4 w-1 rounded-full bg-michelin-blue"></span>
            {{ __('Add a tire') }}
        </h2>

        <label class="flex flex-col gap-1.5">
            <span class="text-[11px] font-black uppercase tracking-wider text-zinc-500">{{ __('Michelin tire') }}</span>
            <select wire:model="productId" class="mt-field" data-test="select-product">
                <option value="" disabled>{{ __('Choose a tire') }}</option>
                @foreach ($this->products as $product)
                    <option value="{{ $product->id }}">{{ $product->web_range_name }}</option>
                @endforeach
            </select>
            @error('productId') <span class="text-xs font-medium text-michelin-danger">{{ $message }}</span> @enderror
        </label>

        <div class="grid grid-cols-2 gap-4">
            <label class="flex flex-col gap-1.5">
                <span class="text-[11px] font-black uppercase tracking-wider text-zinc-500">{{ __('Position') }}</span>
                <select wire:model="position" class="mt-field">
                    <option value="REAR">{{ __('Rear') }}</option>
                    <option value="FRONT">{{ __('Front') }}</option>
                </select>
            </label>

            <label class="flex flex-col gap-1.5">
                <span class="text-[11px] font-black uppercase tracking-wider text-zinc-500">{{ __('Mounted on') }}</span>
                <input type="date" wire:model="mountedAt" class="mt-field" />
            </label>
        </div>

        <label class="flex flex-col gap-1.5">
            <span class="text-[11px] font-black uppercase tracking-wider text-zinc-500">{{ __('Bike odometer at mount (km)') }}</span>
            <input type="number" min="0" wire:model="mountedOdometerKm" class="mt-field" />
        </label>

        <button type="submit" data-test="add-tire-submit"
                class="mt-1 inline-flex items-center justify-center gap-2 rounded-xl bg-michelin-blue px-4 py-3 text-sm font-black uppercase tracking-wider text-white shadow-sm transition hover:bg-michelin-blue-dark focus:outline-none focus:ring-2 focus:ring-michelin-blue/40">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Add the tire') }}
        </button>
    </form>

    <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-bold text-michelin-blue transition hover:gap-2.5 dark:text-michelin-blue-light">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
        {{ __('Back to dashboard') }}
    </a>
</section>
