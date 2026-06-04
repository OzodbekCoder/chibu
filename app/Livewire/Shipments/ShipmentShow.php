<?php

namespace App\Livewire\Shipments;

use App\Models\CurrencyRate;
use App\Models\Shipment;
use App\Services\IpostService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Yuk tafsilotlari · CHIBU')]
class ShipmentShow extends Component
{
    public Shipment $shipment;

    public function mount(Shipment $shipment): void
    {
        abort_unless($shipment->created_by_id === auth()->id(), 403);
        $this->shipment = $shipment->load('client');
    }

    public function accept(): void
    {
        if ($this->shipment->status !== 'CREATED') return;

        $this->shipment->update([
            'status'     => 'DELIVERED',
            'arrived_at' => Carbon::now(),
            'status_at'  => Carbon::now(),
        ]);
        $this->shipment->refresh();

        session()->flash('ok', '✅ Yuk qabul qilindi');
    }

    public function render(IpostService $ipost)
    {
        $userId   = auth()->id();
        $chatId   = (string) (auth()->user()->chat_id ?? $userId);
        $ipostMap = \Illuminate\Support\Facades\Cache::remember(
            "ipost_map_{$userId}", 300, fn () => $ipost->fetchAllByTrack($chatId)
        );
        $yuanRate = (float) (CurrencyRate::latestYuan($userId)?->rate ?? 0);

        return view('livewire.shipments.show', [
            'ii'       => $ipostMap[mb_strtoupper($this->shipment->track_code)] ?? null,
            'yuanRate' => $yuanRate,
        ]);
    }
}
