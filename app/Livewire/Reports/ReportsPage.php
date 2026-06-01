<?php

namespace App\Livewire\Reports;

use App\Services\ReportService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Hisobotlar · CHIBU')]
class ReportsPage extends Component
{
    public function download(string $period, ReportService $service)
    {
        if (!in_array($period, ['day', 'week', 'month'])) {
            $period = 'day';
        }

        $userId = auth()->id();
        $chatId = auth()->user()->chat_id ?? '';

        $info = $service->generate($userId, $period, $chatId);

        return response()->download($info['path'], $info['filename'])->deleteFileAfterSend(true);
    }

    public function render()
    {
        return view('livewire.reports.page');
    }
}
