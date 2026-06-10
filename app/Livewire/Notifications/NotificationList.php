<?php

namespace App\Livewire\Notifications;

use App\Models\ShipmentNotification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Bildirishnomalar · CHIBU')]
class NotificationList extends Component
{
    use WithPagination;

    public function markAllRead(): void
    {
        ShipmentNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        \Illuminate\Support\Facades\Cache::forget('unread_badge_' . auth()->id());
    }

    public function markRead(int $id): void
    {
        ShipmentNotification::where('id', $id)
            ->where('user_id', auth()->id())
            ->update(['is_read' => true]);

        \Illuminate\Support\Facades\Cache::forget('unread_badge_' . auth()->id());
    }

    public function render()
    {
        $userId = auth()->id();

        return view('livewire.notifications.list', [
            'notifications' => ShipmentNotification::with('shipment:id,note')
                ->where('user_id', $userId)
                ->latest()
                ->paginate(20),
            'unreadCount' => ShipmentNotification::where('user_id', $userId)
                ->where('is_read', false)
                ->count(),
        ]);
    }
}
