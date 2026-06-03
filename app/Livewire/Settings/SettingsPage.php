<?php

namespace App\Livewire\Settings;

use App\Models\CurrencyRate;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Sozlamalar · CHIBU')]
class SettingsPage extends Component
{
    #[Validate('required|numeric|gt:0')]
    public string $rate = '';

    public function mount(): void
    {
        $latest = CurrencyRate::latestYuan(auth()->id());
        $this->rate = $latest ? (string) (float) $latest->rate : '';
    }

    public function saveRate(): void
    {
        $this->validate();
        $value  = (float) str_replace([',', ' '], ['.', ''], $this->rate);
        $userId = auth()->id();
        $today  = Carbon::today()->toDateString();

        $existing = CurrencyRate::where('base', 'CNY')
            ->where('quote', 'UZS')
            ->where('rate_date', $today)
            ->where('created_by_id', $userId)
            ->first();

        if ($existing) {
            $existing->update(['rate' => $value]);
        } else {
            CurrencyRate::create([
                'base'          => 'CNY',
                'quote'         => 'UZS',
                'rate_date'     => $today,
                'rate'          => $value,
                'created_by_id' => $userId,
            ]);
        }

        session()->flash('ok', "✅ Yuan kursi yangilandi: 1 ¥ = " . number_format($value, 2) . " so'm");
    }

    public function render()
    {
        $userId = auth()->id();
        return view('livewire.settings.page', [
            'latest'  => CurrencyRate::latestYuan($userId),
            'history' => CurrencyRate::query()
                ->where('base', 'CNY')
                ->where('quote', 'UZS')
                ->where('created_by_id', $userId)
                ->orderByDesc('rate_date')
                ->limit(10)
                ->get(),
        ]);
    }
}
