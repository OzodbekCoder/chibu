<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\Shipment;
use App\Models\ShipmentNotification;
use App\Models\User;
use App\Services\FcmService;
use App\Services\IpostService;
use Illuminate\Console\Command;

class CheckIpostStatuses extends Command
{
    protected $signature   = 'ipost:check-statuses';
    protected $description = 'Check IPOST statuses for all active shipments and notify on change';

    public function handle(IpostService $ipost, FcmService $fcm): int
    {
        $statusLabels = [
            'Warehouse'          => 'Xitoy ombori',
            'Ulugchat'           => 'Xitoy chegara punkti',
            'Osh'                => "O'zbekiston chegara punkti",
            'DistributionCenter' => 'Hududiy omborida',
            'DropZone'           => 'Qabul qilish punkti',
            'Delivered'          => 'Qabul qilindi',
            'CREATED'            => 'Yangi yaratildi',
            'Yiwu'               => "Xitoydan yo'lga chiqdi",
        ];

        // Get all users who have active (non-DELIVERED) shipments
        $userIds = Shipment::whereNotIn('status', ['DELIVERED', 'CANCELLED'])
            ->whereNotNull('ipost_id')
            ->distinct()
            ->pluck('created_by_id');

        foreach ($userIds as $userId) {
            $ipostMap = $ipost->fetchAllByTrack((string) $userId);
            if (empty($ipostMap)) continue;

            $shipments = Shipment::where('created_by_id', $userId)
                ->whereNotIn('status', ['DELIVERED', 'CANCELLED'])
                ->whereNotNull('ipost_id')
                ->get();

            $deviceToken = DeviceToken::forUser($userId);
            $changed     = 0;

            foreach ($shipments as $shipment) {
                $ipostData  = $ipostMap[mb_strtoupper($shipment->track_code)] ?? null;
                $newStatus  = $ipostData['status'] ?? null;

                if (!$newStatus) continue;
                if ($newStatus === $shipment->ipost_status) continue;

                // Status changed
                $oldLabel = $statusLabels[$shipment->ipost_status] ?? ($shipment->ipost_status ?? 'Noma\'lum');
                $newLabel = $statusLabels[$newStatus] ?? $newStatus;

                $message = "📦 {$shipment->track_code}\n{$oldLabel} → {$newLabel}";

                ShipmentNotification::create([
                    'user_id'     => $userId,
                    'shipment_id' => $shipment->id,
                    'track_code'  => $shipment->track_code,
                    'old_status'  => $shipment->ipost_status,
                    'new_status'  => $newStatus,
                    'message'     => $message,
                ]);

                $shipment->update(['ipost_status' => $newStatus]);

                if ($deviceToken) {
                    $fcm->send(
                        $deviceToken,
                        'CHIBU: Yuk holati o\'zgardi',
                        "{$shipment->track_code}: {$newLabel}",
                        ['shipment_id' => (string) $shipment->id]
                    );
                }

                $changed++;
            }

            $this->line("User {$userId}: {$changed} ta o'zgarish");
        }

        return self::SUCCESS;
    }
}
