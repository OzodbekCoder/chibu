<div class="px-4 py-4">
    <!-- Progress -->
    <div class="mb-4">
        <div class="flex justify-between text-xs text-slate-500 mb-1">
            <span>Bosqich {{ $step }} / {{ $totalSteps }}</span>
            <span>{{ (int) ($step / $totalSteps * 100) }}%</span>
        </div>
        <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden">
            <div class="h-full bg-indigo-600 transition-all" style="width: {{ ($step / $totalSteps) * 100 }}%"></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        @if ($step === 1)
            <h2 class="font-semibold mb-3">📋 Trek kod</h2>
            <input wire:model="track_code" type="text" placeholder="YT7597474805854"
                class="w-full rounded-xl border-slate-300 px-3 py-3 uppercase font-mono border focus:border-indigo-500">
            @error('track_code') <div class="text-rose-600 text-sm mt-2">{{ $message }}</div> @enderror

        @elseif ($step === 2)
            <h2 class="font-semibold mb-3">⚖️ Tarif va miqdor</h2>
            <div class="grid grid-cols-3 gap-2 mb-4">
                @foreach ([['kg','⚖️ Kg'], ['piece','📦 Dona'], ['m3','📐 m³']] as [$key, $label])
                    <button type="button" wire:click="$set('tariff_type', '{{ $key }}')"
                        class="py-2.5 rounded-xl border text-sm font-medium {{ $tariff_type === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white border-slate-300 text-slate-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <input wire:model="amount" type="text" inputmode="decimal"
                placeholder="{{ $tariff_type === 'kg' ? 'Og\'irlik (kg)' : ($tariff_type === 'm3' ? 'Hajm (m³)' : 'Soni (dona)') }}"
                class="w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500">
            @error('amount') <div class="text-rose-600 text-sm mt-2">{{ $message }}</div> @enderror

        @elseif ($step === 3)
            <h2 class="font-semibold mb-3">💴 Narx (ixtiyoriy)</h2>
            <input wire:model="price_yuan" type="text" inputmode="decimal" placeholder="Tovar narxi (¥)"
                class="w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500">

        @elseif ($step === 4)
            <h2 class="font-semibold mb-3">👤 Mijoz</h2>
            @error('client_id') <div class="text-rose-600 text-sm mb-2">{{ $message }}</div> @enderror

            @if ($newClientMode)
                <div class="space-y-2">
                    <input wire:model="newClientName" type="text" placeholder="Mijoz ismi" autofocus
                        class="w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500">
                    @error('newClientName') <div class="text-rose-600 text-sm">{{ $message }}</div> @enderror
                    <input wire:model="newClientPhone" type="tel" inputmode="tel" placeholder="+998... (ixtiyoriy)"
                        class="w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500">
                    <button wire:click="$set('newClientMode', false)" type="button"
                        class="text-sm text-slate-500">← Mavjud mijozlardan tanlash</button>
                </div>
            @else
                <button wire:click="$set('newClientMode', true)" type="button"
                    class="w-full mb-3 py-2.5 rounded-xl border-2 border-dashed border-indigo-300 text-indigo-600 text-sm font-medium">
                    ➕ Yangi mijoz qo'shish
                </button>
                <div class="max-h-72 overflow-y-auto space-y-1.5">
                    @forelse ($clients as $c)
                        <button type="button" wire:click="pickClient({{ $c->id }})"
                            class="w-full text-left p-3 rounded-xl border {{ $client_id === $c->id ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 bg-white' }}">
                            <div class="font-medium text-sm">{{ $c->name }}</div>
                            @if ($c->phone) <div class="text-xs text-slate-500">{{ $c->phone }}</div> @endif
                        </button>
                    @empty
                        <div class="text-center text-sm text-slate-400 py-6">Hali mijoz yo'q.</div>
                    @endforelse
                </div>
            @endif

        @elseif ($step === 5)
            <h2 class="font-semibold mb-3">📝 Izoh va tasdiqlash</h2>
            <textarea wire:model="note" rows="2" placeholder="Qizil rangli, katta o'lcham (ixtiyoriy)"
                class="w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500 mb-4"></textarea>

            <div class="bg-slate-50 rounded-xl p-3 text-sm space-y-1.5">
                <div class="flex justify-between"><span class="text-slate-500">Trek:</span> <span class="font-mono font-semibold">{{ $track_code }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Tarif:</span> <span class="font-medium">{{ $tariff_type }} · {{ $amount }}</span></div>
                @if ($price_yuan)
                    <div class="flex justify-between"><span class="text-slate-500">Narx:</span> <span class="font-medium">¥ {{ $price_yuan }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-slate-500">Mijoz:</span>
                    <span class="font-medium">
                        @php $picked = $clients->firstWhere('id', $client_id); @endphp
                        {{ $picked?->name ?? ($newClientName ?: '—') }}
                    </span>
                </div>
                @if ($note)
                    <div class="flex justify-between"><span class="text-slate-500">Izoh:</span> <span class="font-medium">{{ $note }}</span></div>
                @endif
            </div>
        @endif
    </div>

    <!-- Action buttons -->
    <div class="mt-4 grid grid-cols-2 gap-2">
        @if ($step > 1)
            <button wire:click="prevStep" wire:loading.attr="disabled"
                class="py-3 rounded-xl bg-slate-100 text-slate-700 font-medium">← Orqaga</button>
        @else
            <a href="{{ route('app.dashboard') }}" wire:navigate
                class="py-3 rounded-xl bg-slate-100 text-slate-700 font-medium text-center">Bekor qilish</a>
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
