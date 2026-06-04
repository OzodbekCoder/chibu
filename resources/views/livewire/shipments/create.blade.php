<div class="px-4 py-4">
    <!-- Progress -->
    <div class="mb-4">
        <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
            <span>Bosqich {{ $step }} / {{ $totalSteps }}</span>
            <span>{{ (int) ($step / $totalSteps * 100) }}%</span>
        </div>
        <div class="h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
            <div class="h-full bg-indigo-600 transition-all" style="width: {{ ($step / $totalSteps) * 100 }}%"></div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
        @if ($step === 1)
            <h2 class="font-semibold mb-3 dark:text-white">📋 Trek kod</h2>
            <input wire:model="track_code" type="text" placeholder="YT7597474805854"
                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-3 uppercase font-mono border focus:border-indigo-500">
            @error('track_code') <div class="text-rose-500 text-sm mt-2">{{ $message }}</div> @enderror

        @elseif ($step === 2)
            <h2 class="font-semibold mb-3 dark:text-white">⚖️ Tarif va miqdor</h2>
            <div class="grid grid-cols-3 gap-2 mb-4">
                @foreach ([['kg','⚖️ Kg'], ['piece','📦 Dona'], ['m3','📐 m³']] as [$key, $label])
                    <button type="button" wire:click="$set('tariff_type', '{{ $key }}')"
                        class="py-2.5 rounded-xl border text-sm font-medium
                            {{ $tariff_type === $key
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <input wire:model="amount" type="text" inputmode="decimal"
                placeholder="{{ $tariff_type === 'kg' ? 'Og\'irlik (kg)' : ($tariff_type === 'm3' ? 'Hajm (m³)' : 'Soni (dona)') }}"
                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-3 border focus:border-indigo-500">
            @error('amount') <div class="text-rose-500 text-sm mt-2">{{ $message }}</div> @enderror

        @elseif ($step === 3)
            <h2 class="font-semibold mb-3 dark:text-white">💴 Narx (ixtiyoriy)</h2>
            <input wire:model="price_yuan" type="text" inputmode="decimal" placeholder="Tovar narxi (¥)"
                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-3 border focus:border-indigo-500">

        @elseif ($step === 4)
            <h2 class="font-semibold mb-3 dark:text-white">📝 Izoh va tasdiqlash</h2>
            <textarea wire:model="note" rows="2" placeholder="Qo'shimcha izoh (ixtiyoriy)"
                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-3 border focus:border-indigo-500 mb-4"></textarea>

            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-3 text-sm space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-slate-400">Trek:</span>
                    <span class="font-mono font-semibold dark:text-white">{{ $track_code }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-slate-400">Tarif:</span>
                    <span class="font-medium dark:text-white">{{ $tariff_type }} · {{ $amount }}</span>
                </div>
                @if ($price_yuan)
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Narx:</span>
                        <span class="font-medium dark:text-white">¥ {{ $price_yuan }}</span>
                    </div>
                @endif
                @if ($note)
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Izoh:</span>
                        <span class="font-medium dark:text-white">{{ $note }}</span>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="mt-4 grid grid-cols-2 gap-2">
        @if ($step > 1)
            <button wire:click="prevStep" wire:loading.attr="disabled"
                class="py-3 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium">← Orqaga</button>
        @else
            <a href="{{ route('app.dashboard') }}" wire:navigate
                class="py-3 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium text-center">Bekor qilish</a>
        @endif

        @if ($step < $totalSteps)
            <button wire:click="nextStep" wire:loading.attr="disabled"
                class="py-3 rounded-xl bg-indigo-600 text-white font-medium">Keyingi →</button>
        @else
            <button wire:click="save" wire:loading.attr="disabled"
                class="py-3 rounded-xl bg-emerald-600 text-white font-medium">
                <span wire:loading.remove wire:target="save">✅ Saqlash</span>
                <span wire:loading wire:target="save">⏳ Saqlanmoqda...</span>
            </button>
        @endif
    </div>
</div>
