<div class="px-4 py-4 space-y-3">

    <div class="flex items-center justify-between">
        <h1 class="font-semibold text-base dark:text-white">🔔 Bildirishnomalar</h1>
        @if ($unreadCount > 0)
            <button wire:click="markAllRead"
                class="text-xs text-indigo-600 dark:text-indigo-400">
                Barchasini o'qildi deb belgilash
            </button>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-10 text-center">
            <div class="text-5xl mb-3">🔕</div>
            <div class="font-medium dark:text-white">Bildirishnomalar yo'q</div>
            <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                IPOST holati o'zgarganda shu yerda ko'rinadi
            </div>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($notifications as $n)
                <div wire:click="markRead({{ $n->id }})"
                     class="rounded-2xl border p-3 cursor-pointer transition-colors
                         {{ $n->is_read
                             ? 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700'
                             : 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-200 dark:border-indigo-700' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="font-mono text-xs text-slate-500 dark:text-slate-400">
                                {{ $n->track_code }}@if ($n->shipment?->note)<span class="text-slate-400">({{ $n->shipment->note }})</span>@endif
                            </div>
                            <div class="text-sm font-medium mt-0.5 dark:text-white">
                                @if ($n->old_status)
                                    <span class="text-slate-500 dark:text-slate-400">{{ \App\Services\IpostService::STATUS_LABELS[$n->old_status] ?? $n->old_status }}</span>
                                    <span class="text-slate-400 mx-1">→</span>
                                @endif
                                <span class="text-indigo-700 dark:text-indigo-300">{{ \App\Services\IpostService::STATUS_LABELS[$n->new_status] ?? $n->new_status }}</span>
                            </div>
                        </div>
                        <div class="shrink-0 flex flex-col items-end gap-1">
                            <div class="text-[10px] text-slate-400">{{ $n->created_at->diffForHumans() }}</div>
                            @if (!$n->is_read)
                                <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-1">
            {{ $notifications->onEachSide(0)->links() }}
        </div>
    @endif
</div>
