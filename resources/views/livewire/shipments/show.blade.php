@php
    $statusLabels = [
        'CREATED'         => '🆕 Yaratildi',
        'CHINA_WAREHOUSE' => '🏭 Xitoy ombori',
        'ON_THE_WAY'      => "🚛 Yo'lda",
        'CUSTOMS'         => '📋 Bojxona',
        'DELIVERED'       => '✅ Yetkazildi',
        'CANCELLED'       => '❌ Bekor',
    ];
    $iStatusMap = \App\Services\IpostService::STATUS_LABELS;
    $iPayMap    = \App\Services\IpostService::PAY_LABELS;
    $s = $shipment;

    $amountText = match ($s->tariff_type) {
        'kg'    => ($s->weight_kg ?? 0) . ' kg',
        'm3'    => ($s->volume_m3 ?? 0) . ' m³',
        'piece' => ($s->pieces ?? 0) . ' dona',
        default => '-',
    };
    $iPaySom    = (int) ($ii['payAmountSom'] ?? 0);
    $iWeight    = $ii['weight'] ?? null;
    $iImg       = $ii['images'][1] ?? ($ii['images'][0] ?? null);
    $goodsUzs   = ($yuanRate > 0 && $s->price_yuan) ? (float) $s->price_yuan * $yuanRate : 0;
    $hasDelivery = $iPaySom > 0;               // payAmountSom=0 => yo'l kira hali hisoblanmagan
    $totalUzs   = $goodsUzs + $iPaySom;
    $perPiece   = ($s->pieces && $totalUzs > 0) ? (int) ($totalUzs / $s->pieces) : null;

    $ipostStatus = $ii['status'] ?? null;
    $canAccept = $s->status === 'CREATED'
        && $ipostStatus
        && in_array($ipostStatus, ['DropZone', 'DistributionCenter', 'Delivered']);
@endphp

<div class="px-4 py-4 space-y-3">
    @if (session('ok'))
        <div class="bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-sm p-3 rounded-xl border border-emerald-200 dark:border-emerald-700">{{ session('ok') }}</div>
    @endif

    <a href="{{ route('app.shipments.index') }}" wire:navigate
       class="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400">← Ortga</a>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if ($iImg)
            <img src="{{ $iImg }}" alt="rasm" class="w-full h-56 object-cover">
        @endif

        <div class="p-4 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $s->track_code }}</div>
                    <div class="text-lg font-bold mt-0.5 dark:text-white">#{{ $s->id }}</div>
                </div>
                <span class="shrink-0 px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium">
                    {{ $statusLabels[$s->status] ?? $s->status }}
                </span>
            </div>

            {{-- Asosiy ma'lumotlar --}}
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl px-3 py-2">
                    <div class="text-slate-400 text-[11px]">Mijoz</div>
                    <div class="font-medium dark:text-white">{{ $s->client?->name ?? '—' }}</div>
                    @if ($s->client?->phone)
                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $s->client->phone }}</div>
                    @endif
                </div>
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl px-3 py-2">
                    <div class="text-slate-400 text-[11px]">Miqdor</div>
                    <div class="font-medium dark:text-white">{{ $amountText }}</div>
                </div>
                @if ($s->price_yuan)
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl px-3 py-2">
                    <div class="text-slate-400 text-[11px]">Tovar narxi</div>
                    <div class="font-medium dark:text-white">¥ {{ number_format((float) $s->price_yuan, 2) }}</div>
                </div>
                @endif
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl px-3 py-2">
                    <div class="text-slate-400 text-[11px]">Yaratilgan sana</div>
                    <div class="font-medium dark:text-white">{{ $s->created_at->format('d.m.Y H:i') }}</div>
                </div>
                @if ($s->arrived_at)
                <div class="bg-emerald-50 dark:bg-emerald-900/40 rounded-xl px-3 py-2">
                    <div class="text-emerald-600 dark:text-emerald-400 text-[11px]">Qabul sanasi</div>
                    <div class="font-medium text-emerald-700 dark:text-emerald-300">{{ $s->arrived_at->format('d.m.Y H:i') }}</div>
                </div>
                @endif
            </div>

            {{-- IPOST ma'lumotlari --}}
            @if ($ii)
                @php
                    $iStatus = $iStatusMap[$ipostStatus] ?? ($ipostStatus ?? '');
                    $iPayLbl = $iPayMap[$ii['payStatus'] ?? ''] ?? ($ii['payStatus'] ?? '');
                @endphp
                <div class="bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-100 dark:border-indigo-800/50 rounded-xl px-3 py-3 text-sm space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-indigo-900 dark:text-indigo-300">🌐 IPOST holati</span>
                        <span class="text-indigo-700 dark:text-indigo-400">{{ $iPayLbl }}</span>
                    </div>
                    <div class="text-indigo-800 dark:text-indigo-200">{{ $iStatus }}</div>
                    @if ($iWeight)
                        <div class="text-indigo-700 dark:text-indigo-400">⚖️ Vazn: {{ $iWeight }} kg</div>
                    @endif
                    @if ($hasDelivery)
                        <div class="text-indigo-700 dark:text-indigo-400">🚚 Yo'l haqqi: {{ number_format($iPaySom) }} so'm</div>
                    @else
                        <div class="text-amber-600 dark:text-amber-400">🚚 Yo'l haqqi: hali hisoblanmagan</div>
                    @endif
                    @if ($perPiece)
                        <div class="font-semibold pt-1 border-t border-indigo-200/50 dark:border-indigo-700/50
                            {{ $hasDelivery ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                            @if ($hasDelivery)
                                ✅ 1 dona tannarx (yo'l kira bilan): {{ number_format($perPiece) }} so'm
                            @else
                                ⚠️ 1 dona tannarx (yo'l kirasiz): {{ number_format($perPiece) }} so'm
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if ($s->note)
                <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700/50 rounded-xl px-3 py-2 text-sm text-amber-800 dark:text-amber-300">
                    📝 {{ $s->note }}
                </div>
            @endif

            @if ($s->order_url)
                <a href="{{ $s->order_url }}" target="_blank" rel="noopener"
                   class="block text-sm text-indigo-600 dark:text-indigo-400 truncate">🔗 {{ $s->order_url }}</a>
            @endif

            {{-- Qabul qilish --}}
            @if ($canAccept)
                <button wire:click="accept"
                    wire:confirm="Bu yukni qabul qildingizmi?"
                    wire:loading.attr="disabled" wire:target="accept"
                    class="w-full py-3 rounded-xl bg-emerald-600 text-white font-medium active:bg-emerald-700">
                    <span wire:loading.remove wire:target="accept">✅ Qabul qilish</span>
                    <span wire:loading wire:target="accept">⏳...</span>
                </button>
            @endif
        </div>
    </div>
</div>
