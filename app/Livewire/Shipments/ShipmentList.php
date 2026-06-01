<?php

namespace App\Livewire\Shipments;

use App\Models\CurrencyRate;
use App\Models\Shipment;
use App\Services\IpostService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Yuklar · CHIBU')]
class ShipmentList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public function updating($name): void
    {
        if (in_array($name, ['search', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    public function render(IpostService $ipost)
    {
        $userId = auth()->id();
        $chatId = auth()->user()->chat_id ?? '';

        $query = Shipment::with('client')
            ->where('created_by_id', $userId);

        if ($this->search !== '') {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('track_code', 'like', "%{$term}%")
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        $shipments = $query->latest()->paginate(10);

        $ipostMap = Cache::remember("ipost_map_{$userId}", 300, fn () => $ipost->fetchAllByTrack($chatId));
        $yuanRate = (float) (CurrencyRate::latestYuan()?->rate ?? 0);

        return view('livewire.shipments.list', [
            'shipments' => $shipments,
            'ipostMap'  => $ipostMap,
            'yuanRate'  => $yuanRate,
        ]);
    }
}
