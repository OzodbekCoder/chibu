<div class="px-4 py-4 space-y-3">
    @if (session('ok'))
        <div class="bg-emerald-50 text-emerald-700 text-sm p-3 rounded-xl border border-emerald-200">{{ session('ok') }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 p-3 flex gap-2">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Mijoz qidirish..."
            class="flex-1 rounded-xl border-slate-300 px-3 py-2 text-sm border focus:border-indigo-500">
        <button wire:click="openCreate" class="px-3 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium">+ Yangi</button>
    </div>

    @if ($clients->isEmpty())
        <div class="bg-white rounded-2xl p-8 text-center border border-slate-200">
            <div class="text-4xl mb-2">👥</div>
            <div class="font-medium">Mijoz yo'q</div>
        </div>
    @else
        <div class="text-xs text-slate-500 px-1">Jami {{ $clients->total() }} ta</div>
        <div class="space-y-2">
            @foreach ($clients as $c)
                <div class="bg-white rounded-2xl border border-slate-200 p-3">
                    <div class="flex justify-between items-start">
                        <div class="min-w-0">
                            <div class="font-semibold text-sm">{{ $c->name }}</div>
                            @if ($c->phone)
                                <a href="tel:{{ $c->phone }}" class="text-xs text-indigo-600">📞 {{ $c->phone }}</a>
                            @endif
                            @if ($c->notes)
                                <div class="text-[11px] text-slate-500 mt-1">{{ $c->notes }}</div>
                            @endif
                        </div>
                        <span class="text-[11px] text-slate-500">📦 {{ $c->shipments_count }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        <div>{{ $clients->onEachSide(0)->links() }}</div>
    @endif

    <!-- Create modal -->
    @if ($modalOpen)
        <div class="fixed inset-0 z-50 bg-black/40 flex items-end justify-center" wire:click.self="$set('modalOpen', false)">
            <div class="bg-white rounded-t-2xl w-full max-w-md p-4 space-y-3" style="padding-bottom: calc(16px + var(--safe-bottom))">
                <div class="flex justify-between items-center">
                    <h3 class="font-semibold">Yangi mijoz</h3>
                    <button wire:click="$set('modalOpen', false)" class="text-slate-400 text-xl">×</button>
                </div>
                <label class="block">
                    <span class="text-sm text-slate-600">Ism *</span>
                    <input wire:model="name" type="text" autofocus
                        class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2.5 border">
                </label>
                @error('name') <div class="text-rose-600 text-sm">{{ $message }}</div> @enderror
                <label class="block">
                    <span class="text-sm text-slate-600">Telefon</span>
                    <input wire:model="phone" type="tel" inputmode="tel"
                        class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2.5 border">
                </label>
                <label class="block">
                    <span class="text-sm text-slate-600">Izoh (IPOST yozsangiz IPOST'ga avtomatik qo'shiladi)</span>
                    <textarea wire:model="notes" rows="2"
                        class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2.5 border"></textarea>
                </label>
                <button wire:click="save" wire:loading.attr="disabled"
                    class="w-full py-3 rounded-xl bg-indigo-600 text-white font-medium">Saqlash</button>
            </div>
        </div>
    @endif
</div>
