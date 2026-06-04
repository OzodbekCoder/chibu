<?php

namespace App\Livewire\Settings;

use App\Models\Client;
use App\Models\CurrencyRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Sozlamalar · CHIBU')]
class SettingsPage extends Component
{
    use WithFileUploads;

    // Profile
    public string $profileName = '';
    #[Validate('nullable|image|max:2048')]
    public $avatar = null;

    // Own client
    public string $clientName  = '';
    public string $clientPhone = '';

    // Yuan rate
    #[Validate('required|numeric|gt:0')]
    public string $rate = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->profileName = $user->name ?? '';

        $client = Client::where('created_by_id', auth()->id())->first();
        $this->clientName  = $client?->name  ?? $user->name ?? '';
        $this->clientPhone = $client?->phone ?? '';

        $latest = CurrencyRate::latestYuan(auth()->id());
        $this->rate = $latest ? (string) (float) $latest->rate : '';
    }

    public function deleteAvatar(): void
    {
        $user = auth()->user();
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }
        session()->flash('ok', '✅ Rasm o\'chirildi');
    }

    public function saveProfile(): void
    {
        $this->validateOnly('avatar');
        $user = auth()->user();

        if ($this->avatar) {
            // Delete old avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $this->avatar->store('avatars', 'public');
            $user->avatar = $path;
            $this->avatar = null;
        }

        $name = trim($this->profileName);
        if ($name) {
            $user->name = $name;
        }

        $user->save();
        session()->flash('ok', '✅ Profil saqlandi');
    }

    public function saveClient(): void
    {
        $userId = auth()->id();
        $name   = trim($this->clientName);
        $phone  = trim($this->clientPhone) ?: null;

        if (!$name) {
            $this->addError('clientName', 'Ism kiritilishi kerak');
            return;
        }

        Client::updateOrCreate(
            ['created_by_id' => $userId],
            ['name' => $name, 'phone' => $phone]
        );

        session()->flash('ok', '✅ Ma\'lumot saqlandi');
    }

    public function saveRate(): void
    {
        $this->validate(['rate' => 'required|numeric|gt:0']);
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

        session()->flash('ok', "✅ Yuan kursi: 1 ¥ = " . number_format($value, 2) . " so'm");
    }

    public function render()
    {
        $userId = auth()->id();
        return view('livewire.settings.page', [
            'latest'   => CurrencyRate::latestYuan($userId),
            'history'  => CurrencyRate::query()
                ->where('base', 'CNY')
                ->where('quote', 'UZS')
                ->where('created_by_id', $userId)
                ->orderByDesc('rate_date')
                ->limit(7)
                ->get(),
            'avatarUrl' => auth()->user()->avatar
                ? Storage::url(auth()->user()->avatar)
                : null,
        ]);
    }
}
