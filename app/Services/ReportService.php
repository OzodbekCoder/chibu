<?php

namespace App\Services;

use App\Models\CurrencyRate;
use App\Models\Shipment;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportService
{
    /**
     * Generate XLSX from preset period (day|week|month).
     */
    public function generate(int $userId, string $period, string $chatIdHeader = ''): array
    {
        [$from, $to] = match ($period) {
            'week'  => [Carbon::now()->startOfWeek(), Carbon::now()->endOfDay()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfDay()],
            default => [Carbon::today(), Carbon::now()->endOfDay()],
        };

        return $this->generateRange($userId, $from, $to, $chatIdHeader, $period);
    }

    /**
     * Generate XLSX from custom date range.
     */
    public function generateRange(int $userId, Carbon $from, Carbon $to, string $chatIdHeader = '', ?string $periodLabelOverride = null): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->endOfDay();

        $shipments = Shipment::with('client')
            ->where('created_by_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['DELIVERED', 'CANCELLED'])
            ->latest()
            ->get();

        $ipostMap = (new IpostService())->fetchAllByTrack($chatIdHeader);
        $yuanRate = (float) (CurrencyRate::latestYuan($userId)?->rate ?? 0);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'ID', 'Trek Raqam', 'Mijoz', 'Izoh',
            'Miqdor (raqam)', 'Birlik',
            'Tovar narxi (¥)', 'Status', 'Buyurtma sanasi',
            "IPOST dan mi (ha/yo'q)", "IPOST Status",
            "Yo'l haqqi (so'm)", "Bir tovarning tannarxi (so'm)",
            'Link',
        ];
        foreach ($headers as $col => $h) {
            $sheet->setCellValue([$col + 1, 1], $h);
        }
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $sheet->getStyle('A1:N1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00');

        $statusLabel = [
            'CREATED' => 'Yaratildi','CHINA_WAREHOUSE' => 'Xitoy ombori',
            'ON_THE_WAY' => "Yo'lda",'CUSTOMS' => 'Bojxona',
            'DELIVERED' => 'Yetkazildi','CANCELLED' => 'Bekor',
        ];
        $ipostStatusLabels = [
            'Ulugchat' => 'Xitoy chegara','Osh' => 'UZ chegara','DropZone' => 'Qabul punkti',
            'Delivered' => 'Qabul qilindi','CREATED' => 'Yangi','Yiwu' => 'Xitoydan chiqdi',
        ];

        $row = 2;
        foreach ($shipments as $s) {
            $pieces      = (int) ($s->pieces ?? 0);
            $ipost       = $ipostMap[mb_strtoupper($s->track_code)] ?? null;
            $deliveryUzs = (float) ($ipost['payAmountSom'] ?? 0);
            $goodsUzs    = ($yuanRate > 0 && $s->price_yuan) ? (float) $s->price_yuan * $yuanRate : 0;
            $totalUzs    = $goodsUzs + $deliveryUzs;
            $perPiece    = ($pieces > 0 && $totalUzs > 0) ? (int) ($totalUzs / $pieces) : null;

            [$miqdorNum, $birlik] = match ($s->tariff_type) {
                'kg'    => [(float) ($s->weight_kg ?? 0), 'kg'],
                'm3'    => [(float) ($s->volume_m3 ?? 0), 'm³'],
                'piece' => [$pieces, 'dona'],
                default => [0, '-'],
            };

            $rawIpostStatus  = $ipost['status'] ?? '';
            $ipostStatusText = $rawIpostStatus ? ($ipostStatusLabels[$rawIpostStatus] ?? $rawIpostStatus) : '';

            $sheet->setCellValue([1,  $row], $s->id);
            $sheet->setCellValueExplicit([2, $row], $s->track_code, DataType::TYPE_STRING);
            $sheet->setCellValue([3,  $row], $s->client?->name ?? '');
            $sheet->setCellValue([4,  $row], $s->note ?? '');
            $sheet->setCellValue([5,  $row], $miqdorNum);
            $sheet->setCellValue([6,  $row], $birlik);
            $sheet->setCellValue([7,  $row], $s->price_yuan ? (float) $s->price_yuan : '');
            $sheet->setCellValue([8,  $row], $statusLabel[$s->status] ?? $s->status);
            $sheet->setCellValue([9,  $row], $s->created_at->format('d.m.Y H:i'));
            $sheet->setCellValue([10, $row], $s->ipost_id ? 'Ha' : "Yo'q");
            $sheet->setCellValue([11, $row], $ipostStatusText);
            $sheet->setCellValue([12, $row], $deliveryUzs > 0 ? (int) $deliveryUzs : '');
            $sheet->setCellValue([13, $row], $perPiece);
            $sheet->setCellValue([14, $row], $s->order_url ?? '');

            $row++;
        }

        $lastDataRow = $row - 1;
        $sheet->setCellValue([1,  $row], 'Jami:');
        $sheet->setCellValue([5,  $row], "=SUM(E2:E{$lastDataRow})");
        $sheet->setCellValue([7,  $row], "=SUM(G2:G{$lastDataRow})");
        $sheet->setCellValue([12, $row], "=SUM(L2:L{$lastDataRow})");
        $sheet->getStyle('A' . $row . ':N' . $row)->getFont()->setBold(true);

        foreach (range(1, 14) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $periodLabel = $periodLabelOverride ? match ($periodLabelOverride) {
            'week'  => 'haftalik',
            'month' => 'oylik',
            'day'   => 'kunlik',
            default => $periodLabelOverride,
        } : ($from->isSameDay($to)
            ? 'kun_' . $from->format('Y-m-d')
            : 'oraliq_' . $from->format('Y-m-d') . '_' . $to->format('Y-m-d'));

        $filename = 'hisobot_' . $periodLabel . '_' . Carbon::now()->format('His') . '.xlsx';
        $path = storage_path('app/' . $filename);

        (new Xlsx($spreadsheet))->save($path);

        return [
            'path'     => $path,
            'filename' => $filename,
            'count'    => $shipments->count(),
            'period'   => $periodLabel,
            'from'     => $from,
            'to'       => $to,
        ];
    }

    /**
     * Generate archive XLSX: only DELIVERED shipments, date range by arrived_at.
     */
    public function generateArchive(int $userId, Carbon $from, Carbon $to, string $chatIdHeader = ''): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->endOfDay();

        $shipments = Shipment::with('client')
            ->where('created_by_id', $userId)
            ->where('status', 'DELIVERED')
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('arrived_at', [$from, $to])
                  ->orWhere(function ($q2) use ($from, $to) {
                      $q2->whereNull('arrived_at')->whereBetween('status_at', [$from, $to]);
                  });
            })
            ->latest('arrived_at')
            ->get();

        $ipostMap = (new IpostService())->fetchAllByTrack($chatIdHeader);
        $yuanRate = (float) (CurrencyRate::latestYuan($userId)?->rate ?? 0);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $headers = [
            'ID', 'Trek Raqam', 'Mijoz', 'Izoh',
            'Miqdor (raqam)', 'Birlik',
            'Tovar narxi (¥)', 'Qabul sanasi', 'IPOST Vazn (kg)',
            "Yo'l haqqi (so'm)", "Bir tovarning tannarxi (so'm)",
        ];
        foreach ($headers as $col => $h) {
            $sheet->setCellValue([$col + 1, 1], $h);
        }
        $lastCol = 'K';
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF00CC44');

        $row = 2;
        foreach ($shipments as $s) {
            $pieces      = (int) ($s->pieces ?? 0);
            $ipost       = $ipostMap[mb_strtoupper($s->track_code)] ?? null;
            $deliveryUzs = (float) ($ipost['payAmountSom'] ?? 0);
            $goodsUzs    = ($yuanRate > 0 && $s->price_yuan) ? (float) $s->price_yuan * $yuanRate : 0;
            $totalUzs    = $goodsUzs + $deliveryUzs;
            $perPiece    = ($pieces > 0 && $totalUzs > 0) ? (int) ($totalUzs / $pieces) : null;
            $iWeight     = $ipost['weight'] ?? null;

            [$miqdorNum, $birlik] = match ($s->tariff_type) {
                'kg'    => [(float) ($s->weight_kg ?? 0), 'kg'],
                'm3'    => [(float) ($s->volume_m3 ?? 0), 'm³'],
                'piece' => [$pieces, 'dona'],
                default => [0, '-'],
            };

            $arrivedDate = $s->arrived_at?->format('d.m.Y') ?? $s->status_at?->format('d.m.Y') ?? '';

            $sheet->setCellValue([1,  $row], $s->id);
            $sheet->setCellValueExplicit([2, $row], $s->track_code, DataType::TYPE_STRING);
            $sheet->setCellValue([3,  $row], $s->client?->name ?? '');
            $sheet->setCellValue([4,  $row], $s->note ?? '');
            $sheet->setCellValue([5,  $row], $miqdorNum);
            $sheet->setCellValue([6,  $row], $birlik);
            $sheet->setCellValue([7,  $row], $s->price_yuan ? (float) $s->price_yuan : '');
            $sheet->setCellValue([8,  $row], $arrivedDate);
            $sheet->setCellValue([9,  $row], $iWeight ? (float) $iWeight : '');
            $sheet->setCellValue([10, $row], $deliveryUzs > 0 ? (int) $deliveryUzs : '');
            $sheet->setCellValue([11, $row], $perPiece);

            $row++;
        }

        $lastDataRow = $row - 1;
        $sheet->setCellValue([1,  $row], 'Jami:');
        $sheet->setCellValue([5,  $row], "=SUM(E2:E{$lastDataRow})");
        $sheet->setCellValue([7,  $row], "=SUM(G2:G{$lastDataRow})");
        $sheet->setCellValue([9,  $row], "=SUM(I2:I{$lastDataRow})");
        $sheet->setCellValue([10, $row], "=SUM(J2:J{$lastDataRow})");
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);

        foreach (range(1, 11) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $label    = 'arxiv_' . $from->format('Y-m-d') . '_' . $to->format('Y-m-d');
        $filename = 'hisobot_' . $label . '_' . Carbon::now()->format('His') . '.xlsx';
        $path     = storage_path('app/' . $filename);

        (new Xlsx($spreadsheet))->save($path);

        return [
            'path'     => $path,
            'filename' => $filename,
            'count'    => $shipments->count(),
            'from'     => $from,
            'to'       => $to,
        ];
    }
}
