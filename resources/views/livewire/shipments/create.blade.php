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
            <label class="block">
                <span class="text-sm text-slate-600">Trek kodni kiriting</span>
                <input wire:model="track_code" type="text" placeholder="YT7597474805854"
                    class="mt-1 w-full rounded-xl border-slate-300 px-3 py-3 uppercase font-mono border focus:border-indigo-500">
            </label>
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
            <label class="block">
                <span class="text-sm text-slate-600">
                    @if ($tariff_type === 'kg') Og'irlik (kg)
                    @elseif ($tariff_type === 'm3') Hajm (m³)
                    @else Soni (dona)
                    @endif
                </span>
                <input wire:model="amount" type="text" inputmode="decimal" placeholder="12.5"
                    class="mt-1 w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500">
            </label>
            @error('amount') <div class="text-rose-600 text-sm mt-2">{{ $message }}</div> @enderror

        @elseif ($step === 3)
            <h2 class="font-semibold mb-3">🚚 Yetkazish turi</h2>
            <div class="grid grid-cols-2 gap-2">
                @foreach ([['avia','✈️ Avia'], ['avto','🚛 Avto'], ['sea','🚢 Daryo'], ['other','📦 Boshqa']] as [$key, $label])
                    <button type="button" wire:click="$set('delivery_type', '{{ $key }}')"
                        class="py-3 rounded-xl border text-sm font-medium {{ $delivery_type === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white border-slate-300 text-slate-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

        @elseif ($step === 4)
            <h2 class="font-semibold mb-3">💴 Narx va vendor (ixtiyoriy)</h2>
            <label class="block mb-3">
                <span class="text-sm text-slate-600">Tovar narxi (¥, ixtiyoriy)</span>
                <input wire:model="price_yuan" type="text" inputmode="decimal" placeholder="350.50"
                    class="mt-1 w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500">
            </label>
            <label class="block">
                <span class="text-sm text-slate-600">Buyurtma linki yoki vendor (ixtiyoriy)</span>
                <input wire:model="vendor_or_link" type="text" placeholder="https://1688.com/... yoki Vendor ABC"
                    class="mt-1 w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500">
            </label>

        @elseif ($step === 5)
            <h2 class="font-semibold mb-3">👤 Mijoz</h2>
            @error('client_id') <div class="text-rose-600 text-sm mb-2">{{ $message }}</div> @enderror

            @if ($newClientMode)
                <div class="space-y-2">
                    <label class="block">
                        <span class="text-sm text-slate-600">Yangi mijoz ismi</span>
                        <input wire:model="newClientName" type="text" autofocus
                            class="mt-1 w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500">
                    </label>
                    @error('newClientName') <div class="text-rose-600 text-sm">{{ $message }}</div> @enderror
                    <label class="block">
                        <span class="text-sm text-slate-600">Telefon (ixtiyoriy)</span>
                        <input wire:model="newClientPhone" type="tel" inputmode="tel" placeholder="+998..."
                            class="mt-1 w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500">
                    </label>
                    <button wire:click="$set('newClientMode', false)" type="button"
                        class="mt-1 text-sm text-slate-500">← Mavjud mijozlardan tanlash</button>
                </div>
            @else
                <button wire:click="$set('newClientMode', true)" type="button"
                    class="w-full mb-3 py-2.5 rounded-xl border-2 border-dashed border-indigo-300 text-indigo-600 text-sm font-medium hover:bg-indigo-50">
                    ➕ Yangi mijoz qo'shish
                </button>
                <div class="max-h-72 overflow-y-auto -mx-1 px-1 space-y-1.5">
                    @forelse ($clients as $c)
                        <button type="button" wire:click="pickClient({{ $c->id }})"
                            class="w-full text-left p-3 rounded-xl border {{ $client_id === $c->id ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 bg-white' }}">
                            <div class="font-medium text-sm">{{ $c->name }}</div>
                            @if ($c->phone)
                                <div class="text-xs text-slate-500">{{ $c->phone }}</div>
                            @endif
                        </button>
                    @empty
                        <div class="text-center text-sm text-slate-400 py-6">Hali mijoz yo'q. Yangi qo'shing.</div>
                    @endforelse
                </div>
            @endif

        @elseif ($step === 6)
            <h2 class="font-semibold mb-3">📝 Izoh va tasdiqlash</h2>
            <label class="block mb-4">
                <span class="text-sm text-slate-600">Izoh (ixtiyoriy)</span>
                <textarea wire:model="note" rows="2" placeholder="Qizil rangli, katta o'lcham"
                    class="mt-1 w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500"></textarea>
            </label>

            <div class="bg-slate-50 rounded-xl p-3 text-sm space-y-1.5">
                <div class="flex justify-between"><span class="text-slate-500">Trek:</span> <span class="font-mono font-semibold">{{ $track_code }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Tarif:</span> <span class="font-medium">{{ $tariff_type }} · {{ $amount }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Yetkazish:</span> <span class="font-medium">{{ $delivery_type }}</span></div>
                @if ($price_yuan)
                    <div class="flex justify-between"><span class="text-slate-500">Narx:</span> <span class="font-medium">¥ {{ $price_yuan }}</span></div>
                @endif
                @if ($vendor_or_link)
                    <div class="flex justify-between"><span class="text-slate-500">Vendor/Link:</span> <span class="font-medium truncate ml-2">{{ Str::limit($vendor_or_link, 30) }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-slate-500">Mijoz:</span>
                    <span class="font-medium">
                        @php $picked = $clients->firstWhere('id', $client_id); @endphp
                        {{ $picked?->name ?? ($newClientName ?: '—') }}
                    </span>
                </div>
            </div>
        @endif
    </div>

    <!-- Action buttons -->
    <div class="mt-4 grid grid-cols-2 gap-2">
        @if ($step > 1)
            <button wire:click="prevStep" wire:loading.attr="disabled"
                class="py-3 rounded-xl bg-slate-100 text-slate-700 font-medium">
                ← Orqaga
            </button>
        @else
            <a href="{{ route('app.dashboard') }}" wire:navigate
                class="py-3 rounded-xl bg-slate-100 text-slate-700 font-medium text-center">
                Bekor qilish
            </a>
        @endif

        @if ($step < $totalSteps)
            <button wire:click="nextStep" wire:loading.attr="disabled"
                class="py-3 rounded-xl bg-indigo-600 text-white font-medium">
                Keyingi →
            </button>
        @else
            <button wire:click="save" wire:loading.attr="disabled"
                class="py-3 rounded-xl bg-emerald-600 text-white font-medium">
                <span wire:loading.remove wire:target="save">✅ Saqlash</span>
                <span wire:loading wire:target="save">⏳ Saqlanmoqda...</span>
            </button>
        @endif
    </div>
</div>
