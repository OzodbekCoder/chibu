<?php

namespace App\Livewire;

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
            default => null,
        };
    }

    public function applyCustom(): void
    {
        $this->range = 'custom';
        $this->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ], ['to.after_or_equal' => 'Tugash sanasi boshlanishdan keyin bo\'lishi kerak']);
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

        $activeStatuses = ['CREATED', 'CHINA_WAREHOUSE', 'ON_THE_WAY', 'CUSTOMS'];

        // One grouped query covers breakdown + activeCount + deliveredToday
        $statusBreakdown = Shipment::where('created_by_id', $userId)
            ->whereIn('status', $activeStatuses)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');
        $activeCount = (int) $statusBreakdown->sum();

        $deliveredToday = Shipment::where('created_by_id', $userId)
            ->where('status', 'DELIVERED')
            ->whereBetween('arrived_at', [Carbon::today(), Carbon::today()->endOfDay()])
            ->count();

        // count + sum in a single aggregate query
        $range = Shipment::where('created_by_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(price_yuan), 0) as yuan')
            ->first();
        $rangeCount = (int) $range->cnt;
        $rangeYuan  = (float) $range->yuan;

        $latest = Shipment::with('client:id,name')
            ->where('created_by_id', $userId)
            ->whereIn('status', $activeStatuses)
            ->latest()
            ->limit(5)
            ->get();

        $rate = CurrencyRate::latestYuan($userId);

        return view('livewire.dashboard', [
            'activeCount'     => $activeCount,
            'deliveredToday'  => $deliveredToday,
            'rangeCount'      => $rangeCount,
            'rangeYuan'       => $rangeYuan,
            'statusBreakdown' => $statusBreakdown,
            'latest'          => $latest,
            'yuanRate'        => $rate,
        ]);
    }
}
