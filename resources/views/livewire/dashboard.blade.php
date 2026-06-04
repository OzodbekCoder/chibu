<div class="px-4 py-4 space-y-4">

    {{-- Yuan kursi banner --}}
    <div class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-2xl p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs opacity-75">Yuan kursi (CNY → UZS)</div>
                <div class="mt-0.5 text-2xl font-bold">
                    @if ($yuanRate)
                        1 ¥ = {{ number_format((float) $yuanRate->rate, 0) }} so'm
                    @else
                        <span class="text-base opacity-75">Kiritilmagan</span>
                    @endif
                </div>
                @if ($yuanRate)
                    <div class="text-[11px] opacity-60 mt-0.5">{{ $yuanRate->rate_date }}</div>
                @endif
            </div>
            <a href="{{ route('app.settings.index') }}" wire:navigate
               class="bg-white/20 px-3 py-2 rounded-xl text-sm font-medium shrink-0">
                ✏️ Yangilash
            </a>
        </div>
    </div>

    {{-- Asosiy metrikalar --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-[11px] text-slate-400 font-medium uppercase tracking-wide">Faol yuklar</div>
            <div class="mt-1 text-3xl font-bold dark:text-white">{{ $activeCount }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Hozir kuzatilmoqda</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-[11px] text-slate-400 font-medium uppercase tracking-wide">Bugun qabul</div>
            <div class="mt-1 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $deliveredToday }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Yetkazildi</div>
        </div>
    </div>

    {{-- Status bo'yicha taqsimot --}}
    @if ($activeCount > 0)
    @php
        $statusInfo = [
            'CREATED'         => ['🆕 Yaratildi',    'bg-slate-400'],
            'CHINA_WAREHOUSE' => ['🏭 Xitoy ombori', 'bg-amber-400'],
            'ON_THE_WAY'      => ["🚛 Yo'lda",       'bg-blue-400'],
            'CUSTOMS'         => ['📋 Bojxona',       'bg-orange-400'],
        ];
    @endphp
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
        <div class="text-sm font-semibold mb-3 dark:text-white">Holat bo'yicha</div>
        <div class="space-y-2">
            @foreach ($statusInfo as $key => [$label, $color])
                @php $cnt = $statusBreakdown[$key] ?? 0; @endphp
                @if ($cnt > 0)
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full {{ $color }} shrink-0"></div>
                    <div class="flex-1 text-sm text-slate-700 dark:text-slate-200">{{ $label }}</div>
                    <div class="font-semibold text-sm dark:text-white">{{ $cnt }}</div>
                    <div class="w-20 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="{{ $color }} h-full rounded-full"
                             style="width: {{ $activeCount > 0 ? round($cnt / $activeCount * 100) : 0 }}%"></div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Oraliq tanlash --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-3 space-y-2">
        <div class="flex gap-1.5 overflow-x-auto -mx-1 px-1">
            @foreach (['today'=>'📅 Bugun','week'=>'📆 Hafta','month'=>'🗓 Oy','year'=>'📊 Yil','custom'=>'🎯 Oraliq'] as $key=>$label)
                <button wire:click="setRange('{{ $key }}')"
                    class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap shrink-0
                        {{ $range === $key
                            ? 'bg-indigo-600 text-white'
                            : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        @if ($range === 'custom')
            <div class="grid grid-cols-2 gap-2">
                <input wire:model="from" type="date"
                    class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-2 py-2 text-sm border">
                <input wire:model="to" type="date"
                    class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-2 py-2 text-sm border">
            </div>
            @error('to') <div class="text-rose-500 text-xs">{{ $message }}</div> @enderror
            <button wire:click="applyCustom" class="w-full py-2 rounded-lg bg-indigo-600 text-white text-sm">Qo'llash</button>
        @else
            <div class="text-[11px] text-slate-400 px-1">
                {{ \Carbon\Carbon::parse($from)->format('d.m.Y') }} — {{ \Carbon\Carbon::parse($to)->format('d.m.Y') }}
            </div>
        @endif
    </div>

    {{-- Oraliq statistikasi --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-[11px] text-slate-400 font-medium uppercase tracking-wide">Oraliq yuklar</div>
            <div class="mt-1 text-3xl font-bold dark:text-white">{{ number_format($rangeCount) }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Ta qo'shildi</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-[11px] text-slate-400 font-medium uppercase tracking-wide">Tovar narxi</div>
            <div class="mt-1 text-3xl font-bold dark:text-white">{{ number_format((float) $rangeYuan, 0) }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">¥ jami</div>
        </div>
    </div>

    {{-- Tezkor amallar --}}
    <div class="grid grid-cols-3 gap-3">
        <a href="{{ route('app.shipments.create') }}" wire:navigate
           class="bg-indigo-600 rounded-2xl p-3 flex flex-col items-center gap-1 text-white">
            <div class="text-2xl">➕</div>
            <div class="text-xs font-medium">Yangi yuk</div>
        </a>
        <a href="{{ route('app.shipments.search') }}" wire:navigate
           class="bg-white dark:bg-slate-800 rounded-2xl p-3 border border-slate-200 dark:border-slate-700 flex flex-col items-center gap-1">
            <div class="text-2xl">🔍</div>
            <div class="text-xs font-medium text-slate-700 dark:text-slate-200">Qidirish</div>
        </a>
        <a href="{{ route('app.reports.index') }}" wire:navigate
           class="bg-white dark:bg-slate-800 rounded-2xl p-3 border border-slate-200 dark:border-slate-700 flex flex-col items-center gap-1">
            <div class="text-2xl">📊</div>
            <div class="text-xs font-medium text-slate-700 dark:text-slate-200">Hisobot</div>
        </a>
    </div>

    {{-- So'nggi faol yuklar --}}
    @if ($latest->isNotEmpty())
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
        <div class="px-4 py-3 flex items-center justify-between border-b border-slate-100 dark:border-slate-700">
            <div class="font-semibold text-sm dark:text-white">Faol yuklar</div>
            <a href="{{ route('app.shipments.index') }}" wire:navigate class="text-xs text-indigo-600 dark:text-indigo-400">Hammasi →</a>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-700">
            @foreach ($latest as $s)
            @php
                $statusColors = [
                    'CREATED'         => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300',
                    'CHINA_WAREHOUSE' => 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300',
                    'ON_THE_WAY'      => 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
                    'CUSTOMS'         => 'bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300',
                    'DELIVERED'       => 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300',
                ];
                $statusNames = [
                    'CREATED'         => 'Yaratildi',
                    'CHINA_WAREHOUSE' => 'Xitoy ombori',
                    'ON_THE_WAY'      => "Yo'lda",
                    'CUSTOMS'         => 'Bojxona',
                    'DELIVERED'       => 'Yetkazildi',
                ];
            @endphp
            <a href="{{ route('app.shipments.show', $s->id) }}" wire:navigate class="block px-4 py-3 active:bg-slate-50 dark:active:bg-slate-700/50">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-mono text-xs text-slate-400 truncate">{{ $s->track_code }}</div>
                        <div class="font-medium text-sm mt-0.5 truncate dark:text-white">{{ $s->client?->name ?? '—' }}</div>
                    </div>
                    <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $statusColors[$s->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $statusNames[$s->status] ?? $s->status }}
                    </span>
                </div>
                <div class="text-xs text-slate-400 mt-1">{{ $s->created_at->format('d.m.Y') }}</div>
            </a>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-8 text-center">
        <div class="text-4xl mb-2">📦</div>
        <div class="font-medium text-slate-700 dark:text-slate-200">Faol yuk yo'q</div>
        <a href="{{ route('app.shipments.create') }}" wire:navigate class="mt-3 inline-block text-sm text-indigo-600 dark:text-indigo-400">➕ Yangi yuk qo'shish</a>
    </div>
    @endif

</div>
