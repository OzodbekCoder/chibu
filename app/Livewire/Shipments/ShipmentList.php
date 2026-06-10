<?php

namespace App\Livewire\Shipments;

use App\Models\CurrencyRate;
use App\Models\Shipment;
use App\Services\IpostService;
use Carbon\Carbon;
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

    #[Url(as: 'archive')]
    public bool $showArchive = false;

    public function updating($name): void
    {
        if (in_array($name, ['search', 'statusFilter', 'showArchive'])) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->showArchive = false;
        $this->resetPage();
    }

    public function accept(int $id): void
    {
        Shipment::where('id', $id)
            ->where('created_by_id', auth()->id())
            ->whereNotIn('status', ['DELIVERED', 'CANCELLED'])
            ->update([
                'status'     => 'DELIVERED',
                'arrived_at' => Carbon::now(),
                'status_at'  => Carbon::now(),
            ]);

        (new IpostService())->forget(auth()->id());
    }

    public function render(IpostService $ipost)
    {
        $userId = auth()->id();
        $chatId = (string) (auth()->user()->chat_id ?? $userId);

        $query = Shipment::with('client:id,name')
            ->where('created_by_id', $userId);

        if ($this->showArchive) {
            $query->where('status', 'DELIVERED');
        } else {
            $query->whereNotIn('status', ['DELIVERED', 'CANCELLED']);
            if ($this->statusFilter !== '') {
                $query->where('status', $this->statusFilter);
            }
        }

        if ($this->search !== '') {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('track_code', 'like', "%{$term}%")
                  ->orWhere('note', 'like', "%{$term}%")
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        $shipments = $query->latest()->paginate(10);
        $ipostMap  = $ipost->cached($userId, $chatId);
        $yuanRate  = (float) (CurrencyRate::latestYuan($userId)?->rate ?? 0);

        return view('livewire.shipments.list', [
            'shipments' => $shipments,
            'ipostMap'  => $ipostMap,
            'yuanRate'  => $yuanRate,
        ]);
    }
}
