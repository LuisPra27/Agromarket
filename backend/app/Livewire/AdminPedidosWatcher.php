<?php

namespace App\Livewire;

use App\Models\Pedido;
use Filament\Notifications\Notification;
use Livewire\Component;

class AdminPedidosWatcher extends Component
{
    public int $lastPendingCount = 0;

    protected $listeners = ['refresh' => '$refresh'];

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
                ->title($newOrders === 1 ? 'Llegó un nuevo pedido' : "Llegaron {$newOrders} pedidos nuevos")
                ->body('Revisa Caja / Validación para gestionar los pendientes.')
                ->success()
                ->send();

            // Forzar refresh del layout completo de Filament (actualiza badges)
            $this->dispatch('filament:refresh-navigation-items');
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
