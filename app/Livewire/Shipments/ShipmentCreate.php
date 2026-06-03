<?php

namespace App\Livewire\Shipments;

use App\Models\Client;
use App\Models\Shipment;
use App\Services\IpostService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Yangi yuk · CHIBU')]
class ShipmentCreate extends Component
{
    public int $step      = 1;
    public int $totalSteps = 5;

    // Step 1
    public string $track_code = '';

    // Step 2
    public string $tariff_type = 'kg';
    public string $amount      = '';

    // Step 3 — narx
    public ?string $price_yuan = null;

    // Step 4 — mijoz
    public ?int  $client_id    = null;
    public bool  $newClientMode = false;
    public string $newClientName  = '';
    public string $newClientPhone = '';

    // Step 5 — izoh
    public ?string $note = null;

    public function nextStep(): void
    {
        match ($this->step) {
            1 => $this->validateStep1(),
            2 => $this->validateStep2(),
            3 => null,
            4 => $this->validateStep4(),
            default => null,
        };

        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    public function prevStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    protected function validateStep1(): void
    {
        $this->track_code = mb_strtoupper(preg_replace('/\s+/', '', trim($this->track_code)) ?? '');
        $this->validate([
            'track_code' => ['required', 'string', 'min:3', 'max:120', 'unique:shipments,track_code'],
        ], [], ['track_code' => 'Trek kod']);
    }

    protected function validateStep2(): void
    {
        $this->validate([
            'tariff_type' => ['required', 'in:kg,piece,m3'],
            'amount'      => ['required', 'numeric', 'gt:0'],
        ], [], ['amount' => 'Miqdor']);
    }

    protected function validateStep4(): void
    {
        if ($this->newClientMode) {
            $this->validate([
                'newClientName' => ['required', 'string', 'min:2', 'max:255'],
            ], [], ['newClientName' => 'Mijoz ismi']);

            $client = Client::create([
                'name'          => trim($this->newClientName),
                'phone'         => $this->newClientPhone ? trim($this->newClientPhone) : null,
                'created_by_id' => auth()->id(),
            ]);

            $this->client_id     = $client->id;
            $this->newClientMode = false;
        }

        if (!$this->client_id) {
            $this->step = 4;
            throw \Illuminate\Validation\ValidationException::withMessages([
                'client_id' => 'Mijoz tanlanmadi.',
            ]);
        }
    }

    public function pickClient(int $id): void
    {
        $this->client_id     = $id;
        $this->newClientMode = false;
    }

    public function save(IpostService $ipost)
    {
        $this->validateStep1();
        $this->validateStep2();
        $this->validateStep4();

        $userId = auth()->id();
        $amount = (float) str_replace([',', ' '], ['.', ''], $this->amount);

        $data = [
            'track_code'    => $this->track_code,
            'tariff_type'   => $this->tariff_type,
            'delivery_type' => 'avto',
            'client_id'     => $this->client_id,
            'note'          => $this->note ? trim($this->note) : null,
            'price_yuan'    => $this->price_yuan ? (float) str_replace([',', ' '], ['.', ''], $this->price_yuan) : null,
            'status'        => 'CREATED',
            'status_at'     => Carbon::now(),
            'created_by_id' => $userId,
        ];

        if ($this->tariff_type === 'kg')        $data['weight_kg'] = $amount;
        elseif ($this->tariff_type === 'm3')    $data['volume_m3'] = $amount;
        else                                    $data['pieces']    = (int) round($amount);

        $shipment = Shipment::create($data);

        $shipment->load('client');
        if (Str::contains($shipment->client?->notes ?? '', 'IPOST')) {
            $ipost->register($shipment, (string) $userId);
        }

        session()->flash('ok', "✅ #{$shipment->id} · {$shipment->track_code} saqlandi");
        return redirect()->route('app.shipments.index');
    }

    public function render()
    {
        $clients = Client::where('created_by_id', auth()->id())
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'name', 'phone']);

        return view('livewire.shipments.create', ['clients' => $clients]);
    }
}
