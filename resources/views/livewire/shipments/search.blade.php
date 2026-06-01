<div class="px-4 py-4 space-y-3">
    <div class="bg-white rounded-2xl border border-slate-200 p-3">
        <div class="relative">
            <input wire:model.live.debounce.400ms="query" type="search" autofocus
                placeholder="Trek kod, mijoz nomi yoki telefon..."
                class="w-full rounded-xl border border-slate-300 pl-10 pr-10 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
            @if ($query)
                <button wire:click="clear" class="absolute right-3 top-2 text-slate-400 hover:text-slate-700 text-lg">×</button>
            @endif
        </div>
        <div class="mt-2 text-[11px] text-slate-400">Kamida 2 ta belgi kiriting</div>
    </div>

    <div wire:loading.flex wire:target="query" class="hidden justify-center py-4">
        <div class="text-sm text-slate-400">⏳ Qidirilmoqda...</div>
    </div>

    @if (mb_strlen(trim($query)) < 2)
        <div class="bg-white rounded-2xl p-8 text-center border border-slate-200">
            <div class="text-4xl mb-2">🔎</div>
            <div class="font-medium">Qidirishni boshlang</div>
            <div class="text-sm text-slate-500 mt-1">Trek, mijoz nomi yoki telefon bo'yicha qidiring.</div>
        </div>
    @elseif ($results->isEmpty())
        <div wire:loading.remove wire:target="query"
             class="bg-white rounded-2xl p-8 text-center border border-slate-200">
            <div class="text-4xl mb-2">📭</div>
            <div class="font-medium">Topilmadi</div>
            <div class="text-sm text-slate-500 mt-1">"{{ $query }}" bo'yicha hech narsa topilmadi.</div>
        </div>
    @else
        @php
            $statusLabels = [
                'CREATED' => '🆕 Yaratildi','CHINA_WAREHOUSE' => '🏭 Xitoy ombori',
                'ON_THE_WAY' => "🚛 Yo'lda",'CUSTOMS' => '📋 Bojxona',
                'DELIVERED' => '✅ Yetkazildi','CANCELLED' => '❌ Bekor',
            ];
            $deliveryLabels = ['avia'=>'✈️ Avia','avto'=>'🚛 Avto','sea'=>'🚢 Daryo','other'=>'📦 Boshqa'];
            $iStatusMap = \App\Services\IpostService::STATUS_LABELS;
        @endphp
        <div class="text-xs text-slate-500 px-1">{{ $results->count() }} ta natija</div>
        <div class="space-y-2">
            @foreach ($results as $s)
                @php
                    $amountText = match ($s->tariff_type) {
                        'kg' => ($s->weight_kg ?? 0) . ' kg',
                        'm3' => ($s->volume_m3 ?? 0) . ' m³',
                        'piece' => ($s->pieces ?? 0) . ' dona',
                        default => '-',
                    };
                    $ii = $ipostMap[mb_strtoupper($s->track_code)] ?? null;
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 p-3">
                    <div class="flex justify-between items-start mb-1">
                        <div class="font-mono text-xs text-slate-500 truncate">{{ $s->track_code }}</div>
                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-medium">
                            {{ $statusLabels[$s->status] ?? $s->status }}
                        </span>
                    </div>
                    <div class="font-semibold text-sm">#{{ $s->id }} · {{ $s->client?->name ?? '—' }}</div>
                    <div class="text-xs text-slate-500 mt-1">
                        {{ $amountText }} · {{ $deliveryLabels[$s->delivery_type] ?? $s->delivery_type }}
                        · {{ $s->created_at->format('d.m.Y') }}
                    </div>
                    @if ($ii)
                        <div class="mt-2 text-xs text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg px-2 py-1">
                            🌐 {{ $iStatusMap[$ii['status'] ?? ''] ?? ($ii['status'] ?? '') }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
