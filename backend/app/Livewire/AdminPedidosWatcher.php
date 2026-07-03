<?php

namespace App\Livewire;

use App\Models\Pedido;
use Filament\Notifications\Notification;
use Livewire\Component;

class AdminPedidosWatcher extends Component
{
    public int $lastPendingCount = 0;

    public function mount(): void
    {
        $this->lastPendingCount = $this->getPendingCount();
    }

    public function checkPedidos(): void
    {
        $currentPendingCount = $this->getPendingCount();

        if ($currentPendingCount > $this->lastPendingCount) {
            $newOrders = $currentPendingCount - $this->lastPendingCount;

            Notification::make()
                ->title($newOrders === 1 ? 'Llego un nuevo pedido' : "Llegaron {$newOrders} pedidos nuevos")
                ->body('Revisa Caja / Validacion para gestionar los pendientes.')
                ->success()
                ->send();
        }

        $this->lastPendingCount = $currentPendingCount;

        $this->dispatch('pedidos-count-updated', count: $currentPendingCount);
    }

    public function render()
    {
        return view('livewire.admin-pedidos-watcher');
    }

    protected function getPendingCount(): int
    {
        return Pedido::where('estado', 'pendiente_validacion')->count();
    }
}
