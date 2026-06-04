@pushOnce('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
@endPushOnce

@pushOnce('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
@endPushOnce

<div class="px-4 py-4 space-y-3">
    @if (session('ok'))
        <div class="bg-emerald-50 text-emerald-700 text-sm p-3 rounded-xl border border-emerald-200">{{ session('ok') }}</div>
    @endif

    {{-- Profil --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4"
         x-data="{
             showModal: false,
             cropper:   null,
             busy:      false,
             openPicker() { this.$refs.fileInput.click(); },
             onFile(ev) {
                 const f = ev.target.files[0];
                 if (!f) return;
                 const reader = new FileReader();
                 reader.onload = e => {
                     this.showModal = true;
                     this.$nextTick(() => {
                         if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
                         this.cropper = new Cropper(this.$refs.cropImg, {
                             aspectRatio: 1,
                             viewMode: 2,
                             dragMode: 'move',
                             autoCropArea: 0.9,
                             cropBoxResizable: false,
                             responsive: true,
                             minContainerHeight: 260,
                         });
                         this.cropper.replace(e.target.result);
                     });
                 };
                 reader.readAsDataURL(f);
             },
             confirm() {
                 if (!this.cropper || this.busy) return;
                 this.busy = true;
                 this.cropper.getCroppedCanvas({ width: 400, height: 400 })
                     .toBlob(blob => {
                         const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
                         $wire.upload('avatar', file,
                             () => { this.close(); $wire.saveProfile(); },
                             () => { this.busy = false; }
                         );
                     }, 'image/jpeg', 0.9);
             },
             close() {
                 this.showModal = false;
                 this.busy = false;
                 if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
                 this.$refs.fileInput.value = '';
             }
         }">

        <h2 class="font-semibold dark:text-white mb-4">👤 Profil</h2>

        {{-- Avatar: centered circle, click to change --}}
        <div class="flex flex-col items-center gap-3 mb-4">
            <button type="button" @click="openPicker"
                class="relative w-24 h-24 rounded-full overflow-hidden border-4 border-indigo-200 focus:outline-none">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="avatar"
                         class="w-full h-full object-cover" style="display:block; width:96px; height:96px;">
                @else
                    <div class="w-full h-full bg-indigo-100 flex items-center justify-center text-4xl font-bold text-indigo-600">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                @endif
                <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 hover:opacity-100 active:opacity-100 transition-opacity">
                    <span class="text-white text-xs font-medium">✏️</span>
                </div>
            </button>
            <div class="flex items-center gap-2">
                <p class="text-xs text-slate-400">Rasmga bosib o'zgartiring</p>
                @if ($avatarUrl)
                    <button wire:click="deleteAvatar"
                        wire:confirm="Rasmni o'chirasizmi?"
                        class="text-xs text-rose-500 dark:text-rose-400 hover:underline">
                        🗑 O'chirish
                    </button>
                @endif
            </div>
        </div>

        <input type="file" x-ref="fileInput" @change="onFile" accept="image/*" class="sr-only">

        <label class="block mb-3">
            <span class="text-sm text-slate-600">Ismi</span>
            <input wire:model="profileName" type="text"
                class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-2.5 border focus:border-indigo-500 text-sm">
        </label>

        <button wire:click="saveProfile" wire:loading.attr="disabled"
            class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium">
            <span wire:loading.remove wire:target="saveProfile">Profilni saqlash</span>
            <span wire:loading wire:target="saveProfile">⏳...</span>
        </button>

        {{-- Crop modal (portal: fixed, above everything) --}}
        <div x-cloak x-show="showModal"
             class="fixed inset-0 z-[100] bg-black/90 flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 bg-black/60">
                <span class="text-white text-sm font-medium">✂️ Rasmni kesib oling</span>
                <button @click="close" class="text-white/70 text-2xl leading-none">×</button>
            </div>

            <div class="flex-1 relative overflow-hidden">
                <img x-ref="cropImg" src="" alt=""
                     style="display:block; max-width:100%; max-height:100%;">
            </div>

            <div class="px-4 py-4 bg-black/60 flex gap-3">
                <button @click="close" :disabled="busy"
                    class="flex-1 py-3 rounded-xl bg-white/10 text-white text-sm font-medium">
                    Bekor
                </button>
                <button @click="confirm" :disabled="busy"
                    class="flex-1 py-3 rounded-xl bg-indigo-600 text-white text-sm font-medium disabled:opacity-50">
                    <span x-show="!busy">✅ Tasdiqlash</span>
                    <span x-show="busy">⏳ Yuklanmoqda...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- O'z ma'lumotlari --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3">
        <h2 class="font-semibold">📦 Mening ma'lumotlarim</h2>
        <p class="text-xs text-slate-500">Yuklarda ko'rsatiladigan ism va telefon</p>

        <label class="block">
            <span class="text-sm text-slate-600">To'liq ism</span>
            <input wire:model="clientName" type="text"
                class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-2.5 border focus:border-indigo-500 text-sm">
        </label>
        @error('clientName') <div class="text-rose-600 text-xs">{{ $message }}</div> @enderror

        <label class="block">
            <span class="text-sm text-slate-600">Telefon (ixtiyoriy)</span>
            <input wire:model="clientPhone" type="tel" inputmode="tel" placeholder="+998..."
                class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-2.5 border focus:border-indigo-500 text-sm">
        </label>

        <button wire:click="saveClient" wire:loading.attr="disabled"
            class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium">
            <span wire:loading.remove wire:target="saveClient">Saqlash</span>
            <span wire:loading wire:target="saveClient">⏳...</span>
        </button>
    </div>

    {{-- Yuan kursi --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3">
        <h2 class="font-semibold dark:text-white">💴 Yuan kursi</h2>
        @if ($latest)
            <p class="text-xs text-slate-500 dark:text-slate-400">Hozir: <b class="dark:text-white">1 ¥ = {{ number_format((float) $latest->rate, 2) }} so'm</b> · {{ $latest->rate_date }}</p>
        @else
            <p class="text-xs text-slate-500 dark:text-slate-400">Hali kiritilmagan</p>
        @endif
        <input wire:model="rate" type="text" inputmode="decimal" placeholder="2150"
            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-2.5 border focus:border-indigo-500 text-sm">
        @error('rate') <div class="text-rose-600 text-xs">{{ $message }}</div> @enderror
        <button wire:click="saveRate" wire:loading.attr="disabled"
            class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium">
            <span wire:loading.remove wire:target="saveRate">Kursni saqlash</span>
            <span wire:loading wire:target="saveRate">⏳...</span>
        </button>
    </div>

    {{-- Kurs tarixi --}}
    @if ($history->isNotEmpty())
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700 font-semibold text-sm dark:text-white">📈 Kurs tarixi</div>
        <div class="divide-y divide-slate-100 dark:divide-slate-700">
            @foreach ($history as $h)
                <div class="px-4 py-2 flex justify-between text-sm">
                    <span class="text-slate-500 dark:text-slate-400">{{ $h->rate_date }}</span>
                    <span class="font-medium dark:text-white">{{ number_format((float) $h->rate, 2) }} so'm</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Dark / Light rejim --}}
    <div x-data="{ dark: document.documentElement.classList.contains('dark') }"
         class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 flex items-center justify-between">
        <div>
            <div class="text-sm font-medium dark:text-white" x-text="dark ? '🌙 Qorong\'i rejim' : '☀️ Yorug\' rejim'"></div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ekran ko'rinishi</div>
        </div>
        <button type="button" @click="
                dark = !dark;
                document.documentElement.classList.toggle('dark', dark);
                localStorage.theme = dark ? 'dark' : 'light';
            "
            :class="dark ? 'bg-indigo-600' : 'bg-slate-200'"
            class="relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none">
            <span :class="dark ? 'translate-x-6' : 'translate-x-1'"
                  class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200 flex items-center justify-center text-[10px]"
                  x-text="dark ? '🌙' : '☀️'"></span>
        </button>
    </div>

    {{-- Chiqish --}}
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
            class="w-full py-3 rounded-xl bg-rose-50 text-rose-700 font-medium border border-rose-200 text-sm">
            Chiqish
        </button>
    </form>
</div>
