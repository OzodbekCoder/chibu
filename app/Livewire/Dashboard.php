<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\CurrencyRate;
use App\Models\Shipment;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Bosh sahifa · CHIBU')]
class Dashboard extends Component
{
    /** Preset: today | week | month | year | custom */
    #[Url(as: 'r')]
    public string $range = 'month';

    #[Url(as: 'from')]
    public ?string $from = null;

    #[Url(as: 'to')]
    public ?string $to = null;

    public function mount(): void
    {
        if (!$this->from) $this->from = Carbon::now()->startOfMonth()->toDateString();
        if (!$this->to)   $this->to   = Carbon::today()->toDateString();
    }

    public function setRange(string $range): void
    {
        $this->range = $range;
        match ($range) {
            'today' => $this->setDates(Carbon::today(), Carbon::today()),
            'week'  => $this->setDates(Carbon::now()->startOfWeek(), Carbon::today()),
            'month' => $this->setDates(Carbon::now()->startOfMonth(), Carbon::today()),
            'year'  => $this->setDates(Carbon::now()->startOfYear(), Carbon::today()),
            default => null, // custom — preserve current from/to
        };
    }

    public function applyCustom(): void
    {
        $this->range = 'custom';
        $this->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ], [
            'to.after_or_equal' => 'Tugash sanasi boshlanishdan keyin bo\'lishi kerak',
        ]);
    }

    private function setDates(Carbon $from, Carbon $to): void
    {
        $this->from = $from->toDateString();
        $this->to   = $to->toDateString();
    }

    public function render()
    {
        $userId = auth()->id();
        $from   = Carbon::parse($this->from)->startOfDay();
        $to     = Carbon::parse($this->to)->endOfDay();

        $totalShipments = Shipment::where('created_by_id', $userId)->count();
        $clientCount    = Client::where('created_by_id', $userId)->count();

        // In-range stats
        $inRange = Shipment::where('created_by_id', $userId)
            ->whereBetween('created_at', [$from, $to]);

        $rangeCount  = (clone $inRange)->count();
        $rangePieces = (clone $inRange)->sum('pieces') ?? 0;
        $rangeYuan   = (clone $inRange)->sum('price_yuan') ?? 0;

        $latest = Shipment::with('client')
            ->where('created_by_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(5)
            ->get();

        $rate = CurrencyRate::latestYuan();

        return view('livewire.dashboard', [
            'totalShipments' => $totalShipments,
            'clientCount'    => $clientCount,
            'rangeCount'     => $rangeCount,
            'rangePieces'    => $rangePieces,
            'rangeYuan'      => $rangeYuan,
            'latest'         => $latest,
            'yuanRate'       => $rate,
        ]);
    }
}
