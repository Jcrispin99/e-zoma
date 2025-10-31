<?php

namespace App\Livewire\Admin\Pos;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PosSession;

class SessionsList extends Component
{
    use WithPagination;

    public int $posConfigId;

    protected $queryString = [
        'page' => ['except' => 1],
    ];

    public function mount(int $posConfigId): void
    {
        $this->posConfigId = $posConfigId;
    }

    public function render()
    {
        $sessions = PosSession::with('user')
            ->where('pos_config_id', $this->posConfigId)
            ->orderByDesc('opened_at')
            ->paginate(15);

        return view('admin.possessions.sessions-list', [
            'sessions' => $sessions,
        ]);
    }
}
