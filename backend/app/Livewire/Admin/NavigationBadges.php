<?php

namespace App\Livewire\Admin;

use App\Models\Pedido;
use Livewire\Component;

class NavigationBadges extends Component
{
    public int $pendientesValidacion = 0;
    public int $enDelivery = 0;

    public function mount()
    {
        $this->actualizarContadores();
    }

    protected function getListeners()
    {
        return [
            'echo:admin.pedidos,pedido.creado' => 'actualizarContadores',
            'echo:admin.pedidos,pedido.aprobado' => 'actualizarContadores',
            'echo:admin.pedidos,pedido.rechazado' => 'actualizarContadores',
            'echo:admin.pedidos,pedido.listo_para_delivery' => 'actualizarContadores',
            'echo:admin.pedidos,pedido.asignado_delivery' => 'actualizarContadores',
            'echo:admin.pedidos,pedido.aceptado_repartidor' => 'actualizarContadores',
            'echo:admin.pedidos,pedido.entregado' => 'actualizarContadores',
        ];
    }

    public function actualizarContadores()
    {
        $this->pendientesValidacion = Pedido::where('estado', 'pendiente_validacion')->count();
        $this->enDelivery = Pedido::where('metodo_entrega', 'delivery')
            ->whereIn('estado', ['listo_para_delivery', 'en_camino'])
            ->count();
    }

    public function render()
    {
        return view('livewire.admin.navigation-badges');
    }
}