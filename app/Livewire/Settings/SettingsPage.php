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
        $latest = CurrencyRate::latestYuan();
        $this->rate = $latest ? (string) (float) $latest->rate : '';
    }

    public function saveRate(): void
    {
        $this->validate();
        $value = (float) str_replace([',', ' '], ['.', ''], $this->rate);

        CurrencyRate::query()->updateOrCreate(
            [
                'base'      => 'CNY',
                'quote'     => 'UZS',
                'rate_date' => Carbon::today()->toDateString(),
            ],
            [
                'rate'          => $value,
                'created_by_id' => auth()->id(),
            ]
        );

        session()->flash('ok', "✅ Yuan kursi yangilandi: 1 ¥ = " . number_format($value, 2) . " so'm");
    }

    public function render()
    {
        return view('livewire.settings.page', [
            'latest'   => CurrencyRate::latestYuan(),
            'history'  => CurrencyRate::query()
                ->where('base', 'CNY')->where('quote', 'UZS')
                ->orderByDesc('rate_date')->limit(10)->get(),
        ]);
    }
}
