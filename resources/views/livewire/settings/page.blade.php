<div class="px-4 py-4 space-y-3">
    @if (session('ok'))
        <div class="bg-emerald-50 text-emerald-700 text-sm p-3 rounded-xl border border-emerald-200">{{ session('ok') }}</div>
    @endif

    <!-- Yuan rate -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <h2 class="font-semibold mb-1">💴 Yuan kursi</h2>
        <p class="text-xs text-slate-500 mb-3">
            @if ($latest)
                Hozir: <b>1 ¥ = {{ number_format((float) $latest->rate, 2) }} so'm</b> · {{ $latest->rate_date }}
            @else
                Hali kiritilmagan
            @endif
        </p>

        <label class="block">
            <span class="text-sm text-slate-600">Yangi kurs (1 CNY → UZS)</span>
            <input wire:model="rate" type="text" inputmode="decimal" placeholder="2150"
                class="mt-1 w-full rounded-xl border-slate-300 px-3 py-3 border focus:border-indigo-500">
        </label>
        @error('rate') <div class="text-rose-600 text-sm mt-2">{{ $message }}</div> @enderror

        <button wire:click="saveRate" wire:loading.attr="disabled"
            class="mt-3 w-full py-3 rounded-xl bg-indigo-600 text-white font-medium">
            <span wire:loading.remove wire:target="saveRate">Saqlash</span>
            <span wire:loading wire:target="saveRate">⏳ Saqlanmoqda...</span>
        </button>
    </div>

    <!-- Reports link -->
    <a href="{{ route('app.reports.index') }}" wire:navigate
        class="block bg-white rounded-2xl border border-slate-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="font-semibold">📊 Hisobotlar</div>
                <div class="text-xs text-slate-500">Excel formatda yuklab olish</div>
            </div>
            <span class="text-slate-400">→</span>
        </div>
    </a>

    <!-- Clients link -->
    <a href="{{ route('app.clients.index') }}" wire:navigate
        class="block bg-white rounded-2xl border border-slate-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="font-semibold">👥 Mijozlar</div>
                <div class="text-xs text-slate-500">Mijozlar ro'yxati va boshqaruv</div>
            </div>
            <span class="text-slate-400">→</span>
        </div>
    </a>

    <!-- History -->
    @if ($history->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200">
            <div class="px-4 py-3 border-b border-slate-100 font-semibold text-sm">📈 Kurs tarixi</div>
            <div class="divide-y divide-slate-100">
                @foreach ($history as $h)
                    <div class="px-4 py-2 flex justify-between text-sm">
                        <span class="text-slate-500">{{ $h->rate_date }}</span>
                        <span class="font-medium">{{ number_format((float) $h->rate, 2) }} so'm</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Logout big button -->
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full py-3 rounded-xl bg-rose-50 text-rose-700 font-medium border border-rose-200">
            Chiqish
        </button>
    </form>
</div>
