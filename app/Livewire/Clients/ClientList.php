<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Mijozlar · CHIBU')]
class ClientList extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $modalOpen = false;

    #[Validate('required|string|min:2|max:255')]
    public string $name = '';

    public string $phone = '';
    public string $notes = '';

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset(['name', 'phone', 'notes']);
        $this->resetErrorBag();
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->validate();
        Client::create([
            'name'          => trim($this->name),
            'phone'         => $this->phone ? trim($this->phone) : null,
            'notes'         => $this->notes ? trim($this->notes) : null,
            'created_by_id' => auth()->id(),
        ]);
        $this->modalOpen = false;
        session()->flash('ok', 'Mijoz qo\'shildi');
    }

    public function render()
    {
        $userId = auth()->id();
        $q = Client::where('created_by_id', $userId)
            ->withCount('shipments')
            ->orderByDesc('id');

        if ($this->search !== '') {
            $term = trim($this->search);
            $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
        }

        return view('livewire.clients.list', [
            'clients' => $q->paginate(20),
        ]);
    }
}
