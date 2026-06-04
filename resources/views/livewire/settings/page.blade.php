@once
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" defer></script>
@endonce

<div class="px-4 py-4 space-y-3">
    @if (session('ok'))
        <div class="bg-emerald-50 text-emerald-700 text-sm p-3 rounded-xl border border-emerald-200">{{ session('ok') }}</div>
    @endif

    {{-- Profil --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-4"
         x-data="{
             showModal: false,
             cropper:   null,
             busy:      false,
             openPicker() { this.$refs.fileInput.click(); },
             onFile(ev) {
                 const f = ev.target.files[0];
                 if (!f) return;
                 const r = new FileReader();
                 r.onload = e => {
                     this.showModal = true;
                     this.$nextTick(() => {
                         this.$refs.cropImg.src = e.target.result;
                         if (this.cropper) this.cropper.destroy();
                         this.cropper = new Cropper(this.$refs.cropImg, {
                             aspectRatio: 1,
                             viewMode: 2,
                             dragMode: 'move',
                             autoCropArea: 1,
                             cropBoxResizable: false,
                             minContainerHeight: 260,
                             responsive: true,
                         });
                     });
                 };
                 r.readAsDataURL(f);
             },
             confirm() {
                 if (!this.cropper || this.busy) return;
                 this.busy = true;
                 this.cropper.getCroppedCanvas({ width: 400, height: 400 })
                     .toBlob(blob => {
                         const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
                         $wire.upload('avatar', file,
                             () => { this.showModal = false; this.busy = false; $wire.saveProfile(); },
                             () => { this.busy = false; alert('Yuklashda xato'); }
                         );
                     }, 'image/jpeg', 0.9);
             },
             cancel() {
                 this.showModal = false;
                 if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
                 this.$refs.fileInput.value = '';
             }
         }">

        <h2 class="font-semibold">👤 Profil</h2>

        {{-- Avatar circle + pick button --}}
        <div class="flex items-center gap-4">
            <div class="shrink-0 cursor-pointer" @click="openPicker">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="avatar"
                         class="w-20 h-20 rounded-full object-cover border-2 border-indigo-200 ring-2 ring-indigo-100">
                @else
                    <div class="w-20 h-20 rounded-full bg-indigo-100 border-2 border-indigo-200 flex items-center justify-center text-3xl font-bold text-indigo-600">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                @endif
                <div class="text-[10px] text-indigo-600 text-center mt-1">✏️ O'zgartirish</div>
            </div>

            <input type="file" x-ref="fileInput" @change="onFile" accept="image/*" class="sr-only">

            <div class="flex-1">
                <label class="block">
                    <span class="text-sm text-slate-600">Ismi</span>
                    <input wire:model="profileName" type="text"
                        class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2.5 border focus:border-indigo-500 text-sm">
                </label>
            </div>
        </div>

        <button wire:click="saveProfile" wire:loading.attr="disabled"
            class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium">
            <span wire:loading.remove wire:target="saveProfile">Profilni saqlash</span>
            <span wire:loading wire:target="saveProfile">⏳...</span>
        </button>

        {{-- Crop modal --}}
        <div x-show="showModal" x-cloak
             class="fixed inset-0 z-50 bg-black/85 flex flex-col items-center justify-center p-4"
             style="display:none">
            <div class="bg-white rounded-2xl w-full max-w-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 font-semibold text-sm">✂️ Rasmni kesib oling</div>

                <div class="relative" style="height: 280px; background:#000;">
                    <img x-ref="cropImg" src="" alt="" style="max-width:100%; display:block;">
                </div>

                <div class="p-3 flex gap-2">
                    <button @click="cancel"
                        class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-sm font-medium">
                        ✕ Bekor
                    </button>
                    <button @click="confirm" :disabled="busy"
                        class="flex-1 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium disabled:opacity-60">
                        <span x-show="!busy">✅ Tasdiqlash</span>
                        <span x-show="busy">⏳ Yuklanmoqda...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- O'z ma'lumotlari --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3">
        <h2 class="font-semibold">📦 Mening ma'lumotlarim</h2>
        <p class="text-xs text-slate-500">Yuklarda ko'rsatiladigan ism va telefon</p>

        <label class="block">
            <span class="text-sm text-slate-600">To'liq ism</span>
            <input wire:model="clientName" type="text"
                class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2.5 border focus:border-indigo-500 text-sm">
        </label>
        @error('clientName') <div class="text-rose-600 text-xs">{{ $message }}</div> @enderror

        <label class="block">
            <span class="text-sm text-slate-600">Telefon (ixtiyoriy)</span>
            <input wire:model="clientPhone" type="tel" inputmode="tel" placeholder="+998..."
                class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2.5 border focus:border-indigo-500 text-sm">
        </label>

        <button wire:click="saveClient" wire:loading.attr="disabled"
            class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium">
            <span wire:loading.remove wire:target="saveClient">Saqlash</span>
            <span wire:loading wire:target="saveClient">⏳...</span>
        </button>
    </div>

    {{-- Yuan kursi --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3">
        <h2 class="font-semibold">💴 Yuan kursi</h2>
        @if ($latest)
            <p class="text-xs text-slate-500">Hozir: <b>1 ¥ = {{ number_format((float) $latest->rate, 2) }} so'm</b> · {{ $latest->rate_date }}</p>
        @else
            <p class="text-xs text-slate-500">Hali kiritilmagan</p>
        @endif

        <input wire:model="rate" type="text" inputmode="decimal" placeholder="2150"
            class="w-full rounded-xl border-slate-300 px-3 py-2.5 border focus:border-indigo-500 text-sm">
        @error('rate') <div class="text-rose-600 text-xs">{{ $message }}</div> @enderror

        <button wire:click="saveRate" wire:loading.attr="disabled"
            class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium">
            <span wire:loading.remove wire:target="saveRate">Kursni saqlash</span>
            <span wire:loading wire:target="saveRate">⏳...</span>
        </button>
    </div>

    {{-- Kurs tarixi --}}
    @if ($history->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200">
        <div class="px-4 py-3 border-b border-slate-100 font-semibold text-sm">📈 Kurs tarixi</div>
        <div class="divide-y divide-slate-100">
            @foreach ($history as $h)
                <div class="px-4 py-2 flex justify-between text-sm">
                    <span class="text-slate-500">{{ $h->rate_date }}</span>
                    <span class="font-medium">{{ number_format((float) $h->rate, 2) }} so'm</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Chiqish --}}
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
            class="w-full py-3 rounded-xl bg-rose-50 text-rose-700 font-medium border border-rose-200 text-sm">
            Chiqish
        </button>
    </form>
</div>
