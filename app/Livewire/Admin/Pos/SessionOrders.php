<?php

namespace App\Livewire\Admin\Pos;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PosOrder;

class SessionOrders extends Component
{
    use WithPagination;

    public int $sessionId;
    public ?int $expandedOrderId = null;

    protected $queryString = [
        'page' => ['except' => 1],
    ];

    public function mount(int $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function toggle(int $orderId): void
    {
        $this->expandedOrderId = $this->expandedOrderId === $orderId ? null : $orderId;
    }

    public function render()
    {
        $orders = PosOrder::with([
                'customer',
                'payments.paymentMethod',
                'lines.variant.product',
                'lines.variant.attributeValues',
                'sale.journal',
            ])
            ->where('pos_session_id', $this->sessionId)
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.possessions.session-orders', [
            'orders' => $orders,
            'expandedOrderId' => $this->expandedOrderId,
        ]);
    }
}