<div class="px-4 py-4 space-y-3">

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3">
        <h2 class="font-semibold dark:text-white">📊 Hisobot</h2>

        <div class="grid grid-cols-2 gap-2">
            <label class="block">
                <span class="text-xs text-slate-500 dark:text-slate-400">Boshlanish</span>
                <input wire:model="from" type="date"
                    class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-2 text-sm border focus:border-indigo-500">
            </label>
            <label class="block">
                <span class="text-xs text-slate-500 dark:text-slate-400">Tugash</span>
                <input wire:model="to" type="date"
                    class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-2 text-sm border focus:border-indigo-500">
            </label>
        </div>
        @error('from') <div class="text-rose-500 text-xs">{{ $message }}</div> @enderror
        @error('to')   <div class="text-rose-500 text-xs">{{ $message }}</div> @enderror

        <div class="grid grid-cols-2 gap-2">
            <button wire:click="downloadRange" wire:loading.attr="disabled" wire:target="downloadRange"
                class="py-3 rounded-xl bg-indigo-600 text-white font-medium text-sm">
                <span wire:loading.remove wire:target="downloadRange">⬇️ Hisobot</span>
                <span wire:loading wire:target="downloadRange">⏳...</span>
            </button>
            <button wire:click="downloadArchive" wire:loading.attr="disabled" wire:target="downloadArchive"
                class="py-3 rounded-xl bg-emerald-600 text-white font-medium text-sm">
                <span wire:loading.remove wire:target="downloadArchive">✅ Arxiv hisobot</span>
                <span wire:loading wire:target="downloadArchive">⏳...</span>
            </button>
        </div>
        <div class="text-[11px] text-slate-400">Arxiv hisobot — qabul qilingan sanaga ko'ra</div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-2">
        <div class="text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Tezkor yuklab olish</div>
        <div class="grid grid-cols-1 gap-2">
            <button wire:click="download('day')" wire:loading.attr="disabled" wire:target="download"
                class="flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-700/30 active:bg-slate-50 dark:active:bg-slate-700">
                <div class="text-left">
                    <div class="font-medium text-sm dark:text-white">📅 Kunlik</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">Bugungi yuklar</div>
                </div>
                <span class="text-indigo-600 dark:text-indigo-400 text-lg">↓</span>
            </button>
            <button wire:click="download('week')" wire:loading.attr="disabled" wire:target="download"
                class="flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-700/30 active:bg-slate-50 dark:active:bg-slate-700">
                <div class="text-left">
                    <div class="font-medium text-sm dark:text-white">📆 Haftalik</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">Joriy hafta</div>
                </div>
                <span class="text-indigo-600 dark:text-indigo-400 text-lg">↓</span>
            </button>
            <button wire:click="download('month')" wire:loading.attr="disabled" wire:target="download"
                class="flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-700/30 active:bg-slate-50 dark:active:bg-slate-700">
                <div class="text-left">
                    <div class="font-medium text-sm dark:text-white">🗓 Oylik</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">Joriy oy</div>
                </div>
                <span class="text-indigo-600 dark:text-indigo-400 text-lg">↓</span>
            </button>
        </div>
        <div wire:loading wire:target="download" class="text-center text-sm text-slate-500 pt-1">
            ⏳ Hisobot tayyorlanmoqda...
        </div>
    </div>

    <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700/50 rounded-xl p-3 text-xs text-amber-800 dark:text-amber-300">
        💡 Excel fayl: miqdor (raqam), narx (¥), IPOST holati, yo'l haqqi va tannarx hisob-kitobini o'z ichiga oladi.
    </div>
</div>
