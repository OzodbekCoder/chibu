<div class="px-4 py-4 space-y-3">
    @php
        $activeStatuses = ['CREATED','CHINA_WAREHOUSE','ON_THE_WAY','CUSTOMS'];
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
    @endphp

    <!-- Arxiv / Faol toggle + search -->
    <div class="bg-white rounded-2xl border border-slate-200 p-3 space-y-2">
        <div class="flex gap-2">
            <button wire:click="$set('showArchive', false)"
                class="flex-1 py-2 rounded-xl text-sm font-medium {{ !$showArchive ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600' }}">
                📦 Faol
            </button>
            <button wire:click="$set('showArchive', true)"
                class="flex-1 py-2 rounded-xl text-sm font-medium {{ $showArchive ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600' }}">
                ✅ Arxiv
            </button>
        </div>

        <div class="relative">
            <input wire:model.live.debounce.300ms="search" type="search"
                placeholder="Trek yoki mijoz nomi..."
                class="w-full rounded-xl border border-slate-300 pl-10 pr-3 py-2.5 text-sm focus:border-indigo-500">
            <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
        </div>

        @if (!$showArchive)
        <div class="flex gap-2 overflow-x-auto -mx-1 px-1">
            <button wire:click="$set('statusFilter', '')"
                class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap {{ $statusFilter === '' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                Hammasi
            </button>
            @foreach ($activeStatuses as $key)
                <button wire:click="$set('statusFilter', '{{ $key }}')"
                    class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap {{ $statusFilter === $key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                    {{ $statusLabels[$key] }}
                </button>
            @endforeach
        </div>
        @endif
    </div>

    @if ($shipments->total() === 0)
        <div class="bg-white rounded-2xl p-8 text-center border border-slate-200">
            <div class="text-4xl mb-2">📭</div>
            <div class="font-medium">{{ $showArchive ? 'Arxiv bo\'sh' : 'Yuk topilmadi' }}</div>
            @if ($search || $statusFilter)
                <button wire:click="clearFilters" class="mt-3 text-sm text-indigo-600">Filterni tozalash</button>
            @endif
        </div>
    @else
        <div class="text-xs text-slate-500 px-1">
            Jami {{ $shipments->total() }} ta · sahifa {{ $shipments->currentPage() }}/{{ $shipments->lastPage() }}
        </div>

        <div class="space-y-2">
            @foreach ($shipments as $s)
                @php
                    $amountText = match ($s->tariff_type) {
                        'kg'    => ($s->weight_kg ?? 0) . ' kg',
                        'm3'    => ($s->volume_m3 ?? 0) . ' m³',
                        'piece' => ($s->pieces ?? 0) . ' dona',
                        default => '-',
                    };
                    $ii        = $ipostMap[mb_strtoupper($s->track_code)] ?? null;
                    $iPaySom   = (int) ($ii['payAmountSom'] ?? 0);
                    $goodsUzs  = ($yuanRate > 0 && $s->price_yuan) ? (float) $s->price_yuan * $yuanRate : 0;
                    $totalUzs  = $goodsUzs + $iPaySom;
                    $perPiece  = ($s->pieces && $totalUzs > 0) ? (int) ($totalUzs / $s->pieces) : null;
                    $iImg      = $ii['images'][1] ?? ($ii['images'][0] ?? null);
                    $isActive  = !in_array($s->status, ['DELIVERED','CANCELLED']);
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    {{-- IPOST rasm --}}
                    @if ($iImg)
                        <img src="{{ $iImg }}" alt="rasm" class="w-full h-40 object-cover">
                    @endif

                    <div class="p-3 space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-mono text-xs text-slate-500 truncate">{{ $s->track_code }}</div>
                                <div class="font-semibold text-sm mt-0.5 truncate">
                                    #{{ $s->id }} · {{ $s->client?->name ?? '—' }}
                                </div>
                            </div>
                            <span class="shrink-0 px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-medium">
                                {{ $statusLabels[$s->status] ?? $s->status }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-slate-50 rounded-lg px-2 py-1.5">
                                <div class="text-slate-400 text-[10px]">Miqdor</div>
                                <div class="font-medium">{{ $amountText }}</div>
                            </div>
                            @if ($s->price_yuan)
                            <div class="bg-slate-50 rounded-lg px-2 py-1.5">
                                <div class="text-slate-400 text-[10px]">Narx</div>
                                <div class="font-medium">¥ {{ number_format((float) $s->price_yuan, 2) }}</div>
                            </div>
                            @endif
                            <div class="bg-slate-50 rounded-lg px-2 py-1.5">
                                <div class="text-slate-400 text-[10px]">Sana</div>
                                <div class="font-medium">{{ $s->created_at->format('d.m.Y') }}</div>
                            </div>
                            @if ($perPiece)
                            <div class="bg-emerald-50 rounded-lg px-2 py-1.5">
                                <div class="text-emerald-600 text-[10px]">1 dona tannarx</div>
                                <div class="font-semibold text-emerald-700">{{ number_format($perPiece) }} so'm</div>
                            </div>
                            @endif
                        </div>

                        @if ($ii)
                            @php
                                $iStatus = $iStatusMap[$ii['status'] ?? ''] ?? ($ii['status'] ?? '');
                                $iPayLbl = $iPayMap[$ii['payStatus'] ?? ''] ?? ($ii['payStatus'] ?? '');
                            @endphp
                            <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-2 py-1.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium text-indigo-900">🌐 {{ $iStatus }}</div>
                                    <div class="text-indigo-700">{{ $iPayLbl }}</div>
                                </div>
                                @if ($iPaySom > 0)
                                    <div class="text-indigo-700 mt-0.5">🚚 {{ number_format($iPaySom) }} so'm</div>
                                @endif
                            </div>
                        @endif

                        @if ($s->note)
                            <div class="text-xs text-slate-600 italic">📝 {{ $s->note }}</div>
                        @endif

                        {{-- Qabul qilish tugmasi --}}
                        @if ($isActive)
                            <button wire:click="accept({{ $s->id }})"
                                wire:confirm="Bu yukni qabul qildingizmi?"
                                wire:loading.attr="disabled"
                                wire:target="accept({{ $s->id }})"
                                class="w-full py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-medium active:bg-emerald-700">
                                <span wire:loading.remove wire:target="accept({{ $s->id }})">✅ Qabul qilish</span>
                                <span wire:loading wire:target="accept({{ $s->id }})">⏳...</span>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-2">
            {{ $shipments->onEachSide(0)->links() }}
        </div>
    @endif
</div>
