<?php

namespace App\Livewire\Reports;

use App\Services\ReportService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Hisobotlar · CHIBU')]
class ReportsPage extends Component
{
    public string $from = '';
    public string $to   = '';

    public function mount(): void
    {
        $this->from = Carbon::now()->startOfMonth()->toDateString();
        $this->to   = Carbon::today()->toDateString();
    }

    public function download(string $period, ReportService $service)
    {
        $userId = auth()->id();
        $info   = $service->generate($userId, $period, (string) $userId);
        return response()->download($info['path'], $info['filename'])->deleteFileAfterSend(true);
    }

    public function downloadRange(ReportService $service)
    {
        $this->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ], ['to.after_or_equal' => 'Tugash sanasi boshlanishdan keyin bo\'lishi kerak']);

        $userId = auth()->id();
        $info   = $service->generateRange(
            $userId,
            Carbon::parse($this->from),
            Carbon::parse($this->to),
            (string) $userId
        );
        return response()->download($info['path'], $info['filename'])->deleteFileAfterSend(true);
    }

    public function downloadArchive(ReportService $service)
    {
        $this->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ], ['to.after_or_equal' => 'Tugash sanasi boshlanishdan keyin bo\'lishi kerak']);

        $userId = auth()->id();
        $info   = $service->generateArchive(
            $userId,
            Carbon::parse($this->from),
            Carbon::parse($this->to),
            (string) $userId
        );
        return response()->download($info['path'], $info['filename'])->deleteFileAfterSend(true);
    }

    public function render()
    {
        return view('livewire.reports.page');
    }
}
