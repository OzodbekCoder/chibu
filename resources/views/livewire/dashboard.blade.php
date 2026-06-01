<div class="px-4 py-4 space-y-4">
    <!-- Date range selector -->
    <div class="bg-white rounded-2xl border border-slate-200 p-3 space-y-2">
        <div class="flex gap-1.5 overflow-x-auto -mx-1 px-1">
            @php
                $presets = [
                    'today' => '📅 Bugun',
                    'week'  => '📆 Hafta',
                    'month' => '🗓 Oy',
                    'year'  => '📊 Yil',
                    'custom'=> '🎯 Sana oralig\'i',
                ];
            @endphp
            @foreach ($presets as $key => $label)
                <button wire:click="setRange('{{ $key }}')"
                    class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap shrink-0
                        {{ $range === $key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($range === 'custom')
            <div class="grid grid-cols-2 gap-2 pt-1">
                <label class="block">
                    <span class="text-[11px] text-slate-500">Boshlanish</span>
                    <input wire:model="from" type="date"
                        class="mt-0.5 w-full rounded-lg border-slate-300 px-2 py-2 text-sm border focus:border-indigo-500">
                </label>
                <label class="block">
                    <span class="text-[11px] text-slate-500">Tugash</span>
                    <input wire:model="to" type="date"
                        class="mt-0.5 w-full rounded-lg border-slate-300 px-2 py-2 text-sm border focus:border-indigo-500">
                </label>
            </div>
            @error('to') <div class="text-rose-600 text-xs">{{ $message }}</div> @enderror
            <button wire:click="applyCustom"
                class="w-full py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium">
                Qo'llash
            </button>
        @else
            <div class="text-[11px] text-slate-500 px-1">
                {{ \Carbon\Carbon::parse($from)->format('d.m.Y') }} — {{ \Carbon\Carbon::parse($to)->format('d.m.Y') }}
            </div>
        @endif
    </div>

    <!-- Stats grid (range-based) -->
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white rounded-2xl p-4 border border-slate-200">
            <div class="text-xs text-slate-500">Tanlangan oraliq</div>
            <div class="mt-1 text-2xl font-bold">{{ number_format($rangeCount) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">📦 yuk</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200">
            <div class="text-xs text-slate-500">Tovar (¥)</div>
            <div class="mt-1 text-2xl font-bold">{{ number_format((float) $rangeYuan, 0) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">💴 oraliqda</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200">
            <div class="text-xs text-slate-500">Jami yuklar</div>
            <div class="mt-1 text-2xl font-bold">{{ number_format($totalShipments) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">🗂 hisoblandi</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200">
            <div class="text-xs text-slate-500">Mijozlar</div>
            <div class="mt-1 text-2xl font-bold">{{ number_format($clientCount) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">👥 ro'yxatda</div>
        </div>
    </div>

    <!-- Yuan rate card -->
    <div class="bg-gradient-to-br from-indigo-500 to-blue-600 rounded-2xl p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs opacity-80">Yuan kursi</div>
                <div class="mt-1 text-xl font-bold">
                    @if ($yuanRate)
                        1 ¥ = {{ number_format((float) $yuanRate->rate, 2) }} so'm
                    @else
                        kiritilmagan
                    @endif
                </div>
                @if ($yuanRate)
                    <div class="text-[11px] opacity-70 mt-1">{{ $yuanRate->rate_date }}</div>
                @endif
            </div>
            <a href="{{ route('app.settings.index') }}" wire:navigate
               class="bg-white/20 hover:bg-white/30 active:bg-white/40 px-3 py-2 rounded-xl text-sm font-medium">
                Yangilash
            </a>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="grid grid-cols-3 gap-3">
        <a href="{{ route('app.shipments.create') }}" wire:navigate
           class="bg-white rounded-2xl p-3 border border-slate-200 flex flex-col items-center gap-1 hover:border-indigo-300">
            <div class="text-2xl">➕</div>
            <div class="text-xs font-medium">Yangi yuk</div>
        </a>
        <a href="{{ route('app.shipments.search') }}" wire:navigate
           class="bg-white rounded-2xl p-3 border border-slate-200 flex flex-col items-center gap-1 hover:border-indigo-300">
            <div class="text-2xl">🔍</div>
            <div class="text-xs font-medium">Qidirish</div>
        </a>
        <a href="{{ route('app.reports.index', ['from' => $from, 'to' => $to]) }}" wire:navigate
           class="bg-white rounded-2xl p-3 border border-slate-200 flex flex-col items-center gap-1 hover:border-indigo-300">
            <div class="text-2xl">📊</div>
            <div class="text-xs font-medium">Hisobot</div>
        </a>
    </div>

    <!-- Latest shipments in range -->
    <div class="bg-white rounded-2xl border border-slate-200">
        <div class="px-4 py-3 flex items-center justify-between border-b border-slate-100">
            <div class="font-semibold text-sm">Oraliqdagi so'nggi yuklar</div>
            <a href="{{ route('app.shipments.index') }}" wire:navigate class="text-xs text-indigo-600">Hammasi →</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($latest as $s)
                <div class="px-4 py-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-mono text-xs text-slate-500">{{ $s->track_code }}</div>
                            <div class="font-medium text-sm mt-0.5">{{ $s->client?->name ?? '—' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-slate-500">{{ $s->created_at->format('d.m.Y') }}</div>
                            <div class="text-xs mt-0.5">
                                <span class="inline-block px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-medium">
                                    {{ $s->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-slate-400">
                    Bu oraliqda yuk yo'q.
                </div>
            @endforelse
        </div>
    </div>
</div>
