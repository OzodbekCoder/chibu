@php
    $avatar = auth()->user()->avatar
        ? \Illuminate\Support\Facades\Storage::url(auth()->user()->avatar)
        : null;
    $initial = mb_strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1));
@endphp
<header class="pt-safe bg-white border-b border-slate-200 sticky top-0 z-30">
    <div class="px-4 py-2.5 flex items-center justify-between">
        <a href="{{ route('app.settings.index') }}" wire:navigate class="flex items-center gap-2.5">
            @if ($avatar)
                <img src="{{ $avatar }}" alt="avatar"
                     class="w-9 h-9 rounded-full object-cover border-2 border-indigo-200 shrink-0">
            @else
                <div class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
                    {{ $initial }}
                </div>
            @endif
            <div>
                <div class="text-sm font-semibold leading-tight">{{ auth()->user()->name }}</div>
                <div class="text-[10px] text-slate-400 leading-tight">CHIBU</div>
            </div>
        </a>
        <a href="{{ route('app.settings.index') }}" wire:navigate
           class="text-xs text-slate-400 px-2 py-1">⚙️</a>
    </div>
</header>
