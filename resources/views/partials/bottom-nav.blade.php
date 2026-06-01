@php
    $current = request()->route()?->getName();
    $tabs = [
        ['name' => 'app.dashboard',         'icon' => '🏠', 'label' => 'Bosh'],
        ['name' => 'app.shipments.index',   'icon' => '📦', 'label' => 'Yuklar'],
        ['name' => 'app.shipments.create',  'icon' => '➕', 'label' => 'Yangi'],
        ['name' => 'app.shipments.search',  'icon' => '🔍', 'label' => 'Qidir'],
        ['name' => 'app.settings.index',    'icon' => '⚙️', 'label' => 'Sozlama'],
    ];
@endphp
<nav class="fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 z-30" style="padding-bottom: var(--safe-bottom)">
    <div class="grid grid-cols-5">
        @foreach ($tabs as $tab)
            @php $active = $current === $tab['name']; @endphp
            <a href="{{ route($tab['name']) }}"
               wire:navigate
               class="flex flex-col items-center justify-center py-2 gap-0.5 {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">
                <span class="text-xl leading-none">{{ $tab['icon'] }}</span>
                <span class="text-[10px] font-medium">{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
