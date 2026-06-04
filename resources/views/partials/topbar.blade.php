@php
    $avatar  = auth()->user()->avatar
        ? \Illuminate\Support\Facades\Storage::url(auth()->user()->avatar)
        : null;
    $initial  = mb_strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1));
    $unreadCount = \App\Models\ShipmentNotification::where('user_id', auth()->id())
        ->where('is_read', false)->count();
@endphp
<header class="pt-safe bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-30 transition-colors">
    <div class="px-4 py-2.5 flex items-center justify-between">
        <a href="{{ route('app.settings.index') }}" wire:navigate class="flex items-center gap-2.5">
            @if ($avatar)
                <img src="{{ $avatar }}" alt="avatar"
                     class="w-9 h-9 rounded-full object-cover border-2 border-indigo-200 shrink-0"
                     style="display:block; width:36px; height:36px;">
            @else
                <div class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
                    {{ $initial }}
                </div>
            @endif
            <div>
                <div class="text-sm font-semibold leading-tight dark:text-white">{{ auth()->user()->name }}</div>
                <div class="text-[10px] text-slate-400 leading-tight">CHIBU</div>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <a href="{{ route('app.notifications.index') }}" wire:navigate
               class="relative px-2 py-1 text-slate-500 dark:text-slate-400">
                🔔
                @if ($unreadCount > 0)
                    <span class="absolute top-0.5 right-0.5 min-w-[16px] h-4 bg-rose-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center px-0.5">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </a>
            <a href="{{ route('app.settings.index') }}" wire:navigate
               class="text-slate-400 dark:text-slate-500 px-2 py-1">⚙️</a>
        </div>
    </div>
</header>
