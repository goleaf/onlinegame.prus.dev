<?php

namespace App\Livewire\Game;

use App\Models\Game\Report;
use Livewire\Component;

class ReportDetail extends Component
{
    public Report $report;

    public function mount(Report $report)
    {
        $this->report = $report;
    }

    public function render()
    {
        return view('livewire.game.report-detail');
    }
}
