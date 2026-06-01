<div class="px-4 py-4 space-y-3">
    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <h2 class="font-semibold mb-1">📊 Hisobot eksport qilish</h2>
        <p class="text-xs text-slate-500 mb-4">Excel (.xlsx) faylini yuklab oling</p>

        <div class="grid grid-cols-1 gap-2">
            <button wire:click="download('day')" wire:loading.attr="disabled" wire:target="download"
                class="flex items-center justify-between p-3 rounded-xl border border-slate-200 hover:border-indigo-400 active:bg-slate-50">
                <div class="text-left">
                    <div class="font-medium text-sm">📅 Kunlik hisobot</div>
                    <div class="text-xs text-slate-500">Bugungi yuklar</div>
                </div>
                <span class="text-indigo-600">↓</span>
            </button>

            <button wire:click="download('week')" wire:loading.attr="disabled" wire:target="download"
                class="flex items-center justify-between p-3 rounded-xl border border-slate-200 hover:border-indigo-400 active:bg-slate-50">
                <div class="text-left">
                    <div class="font-medium text-sm">📆 Haftalik hisobot</div>
                    <div class="text-xs text-slate-500">Joriy hafta</div>
                </div>
                <span class="text-indigo-600">↓</span>
            </button>

            <button wire:click="download('month')" wire:loading.attr="disabled" wire:target="download"
                class="flex items-center justify-between p-3 rounded-xl border border-slate-200 hover:border-indigo-400 active:bg-slate-50">
                <div class="text-left">
                    <div class="font-medium text-sm">🗓 Oylik hisobot</div>
                    <div class="text-xs text-slate-500">Joriy oy</div>
                </div>
                <span class="text-indigo-600">↓</span>
            </button>
        </div>

        <div wire:loading wire:target="download" class="mt-3 text-center text-sm text-slate-500">
            ⏳ Hisobot tayyorlanmoqda...
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800">
        💡 Eksport qilingan fayl IPOST'dan kelgan ma'lumotlarni va Yuan kursi asosidagi tannarx hisob-kitobini o'z ichiga oladi.
    </div>
</div>
