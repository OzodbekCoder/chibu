<?php

namespace App\Livewire\Shipments;

use App\Models\CurrencyRate;
use App\Models\Shipment;
use App\Services\IpostService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Qidirish · CHIBU')]
class ShipmentSearch extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    public function clear(): void
    {
        $this->query = '';
    }

    public function render(IpostService $ipost)
    {
        $userId = auth()->id();
        $chatId = auth()->user()->chat_id ?? '';

        $results = collect();
        if (mb_strlen(trim($this->query)) >= 2) {
            $term = trim($this->query);
            $results = Shipment::with('client')
                ->where('created_by_id', $userId)
                ->where(function ($q) use ($term) {
                    $q->where('track_code', 'like', "%{$term}%")
                      ->orWhere('vendor_name', 'like', "%{$term}%")
                      ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
                })
                ->latest()
                ->limit(20)
                ->get();
        }

        $ipostMap = $results->isNotEmpty() ? $ipost->fetchAllByTrack($chatId) : [];
        $yuanRate = (float) (CurrencyRate::latestYuan()?->rate ?? 0);

        return view('livewire.shipments.search', [
            'results'  => $results,
            'ipostMap' => $ipostMap,
            'yuanRate' => $yuanRate,
        ]);
    }
}
