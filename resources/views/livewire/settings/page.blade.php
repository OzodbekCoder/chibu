<div class="px-4 py-4 space-y-3">
    @if (session('ok'))
        <div class="bg-emerald-50 text-emerald-700 text-sm p-3 rounded-xl border border-emerald-200">{{ session('ok') }}</div>
    @endif

    {{-- Profil --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-4">
        <h2 class="font-semibold">👤 Profil</h2>

        {{-- Avatar --}}
        <div class="flex items-center gap-4">
            <div class="relative shrink-0">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="avatar"
                         class="w-20 h-20 rounded-full object-cover border-2 border-slate-200">
                @else
                    <div class="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center text-3xl border-2 border-slate-200">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <label class="block cursor-pointer">
                    <span class="text-xs text-slate-500">Rasm tanlash (max 2MB)</span>
                    <input wire:model="avatar" type="file" accept="image/*" class="sr-only">
                    <div class="mt-1 w-full py-2 px-3 rounded-xl border border-dashed border-indigo-300 text-indigo-600 text-sm text-center">
                        📷 Rasm yuklash
                    </div>
                </label>
                @if ($avatar)
                    <div class="text-xs text-emerald-600 mt-1">✅ Yangi rasm tanlandi</div>
                @endif
                @error('avatar') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        <label class="block">
            <span class="text-sm text-slate-600">Ismi</span>
            <input wire:model="profileName" type="text"
                class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2.5 border focus:border-indigo-500 text-sm">
        </label>

        <button wire:click="saveProfile" wire:loading.attr="disabled"
            class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium">
            <span wire:loading.remove wire:target="saveProfile">Profilni saqlash</span>
            <span wire:loading wire:target="saveProfile">⏳...</span>
        </button>
    </div>

    {{-- O'z ma'lumotlari (bitta mijoz) --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3">
        <h2 class="font-semibold">📦 Mening ma'lumotlarim</h2>
        <p class="text-xs text-slate-500">Yuklarda ko'rsatiladigan ism va telefon</p>

        <label class="block">
            <span class="text-sm text-slate-600">To'liq ism</span>
            <input wire:model="clientName" type="text"
                class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2.5 border focus:border-indigo-500 text-sm">
        </label>
        @error('clientName') <div class="text-rose-600 text-xs">{{ $message }}</div> @enderror

        <label class="block">
            <span class="text-sm text-slate-600">Telefon (ixtiyoriy)</span>
            <input wire:model="clientPhone" type="tel" inputmode="tel" placeholder="+998..."
                class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2.5 border focus:border-indigo-500 text-sm">
        </label>

        <button wire:click="saveClient" wire:loading.attr="disabled"
            class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium">
            <span wire:loading.remove wire:target="saveClient">Saqlash</span>
            <span wire:loading wire:target="saveClient">⏳...</span>
        </button>
    </div>

    {{-- Yuan kursi --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3">
        <h2 class="font-semibold">💴 Yuan kursi</h2>
        @if ($latest)
            <p class="text-xs text-slate-500">Hozir: <b>1 ¥ = {{ number_format((float) $latest->rate, 2) }} so'm</b> · {{ $latest->rate_date }}</p>
        @else
            <p class="text-xs text-slate-500">Hali kiritilmagan</p>
        @endif

        <input wire:model="rate" type="text" inputmode="decimal" placeholder="2150"
            class="w-full rounded-xl border-slate-300 px-3 py-2.5 border focus:border-indigo-500 text-sm">
        @error('rate') <div class="text-rose-600 text-xs">{{ $message }}</div> @enderror

        <button wire:click="saveRate" wire:loading.attr="disabled"
            class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium">
            <span wire:loading.remove wire:target="saveRate">Kursni saqlash</span>
            <span wire:loading wire:target="saveRate">⏳...</span>
        </button>
    </div>

    {{-- Kurs tarixi --}}
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

    {{-- Chiqish --}}
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
            class="w-full py-3 rounded-xl bg-rose-50 text-rose-700 font-medium border border-rose-200 text-sm">
            Chiqish
        </button>
    </form>
</div>
