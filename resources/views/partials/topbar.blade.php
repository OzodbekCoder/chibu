<header class="pt-safe bg-white border-b border-slate-200 sticky top-0 z-30">
    <div class="px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white font-bold">📦</div>
            <div>
                <div class="text-base font-semibold leading-tight">CHIBU</div>
                <div class="text-[11px] text-slate-500 leading-tight">{{ auth()->user()->name }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-rose-600 px-2 py-1">Chiqish</button>
        </form>
    </div>
</header>
