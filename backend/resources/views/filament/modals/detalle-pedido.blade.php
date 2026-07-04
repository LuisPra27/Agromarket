<div class="space-y-4 p-2">

    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <p class="text-sm text-gray-500">Pedido</p>
            <p class="text-2xl font-bold text-gray-900">#{{ $pedido->id }}</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500">Total</p>
            <p class="text-2xl font-bold text-green-600">${{ number_format($pedido->total, 2) }}</p>
        </div>
    </div>

    {{-- Estado y modalidad --}}
    <div class="flex gap-2">
        <span class="px-3 py-1 rounded-full text-xs font-semibold
            {{ match($pedido->estado) {
                'pendiente_validacion' => 'bg-yellow-100 text-yellow-800',
                'preparando'           => 'bg-blue-100 text-blue-800',
                'listo_para_delivery'  => 'bg-purple-100 text-purple-800',
                'en_camino'            => 'bg-orange-100 text-orange-800',
                'entregado'            => 'bg-green-100 text-green-800',
                'rechazado'            => 'bg-red-100 text-red-800',
                default                => 'bg-gray-100 text-gray-800',
            } }}">
            {{ str_replace('_', ' ', ucfirst($pedido->estado)) }}
        </span>
        <span class="px-3 py-1 rounded-full text-xs font-semibold
            {{ $pedido->metodo_entrega === 'delivery' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' }}">
            {{ $pedido->metodo_entrega === 'delivery' ? '🛵 Delivery' : '🏪 Retiro' }}
        </span>
    </div>

    {{-- Cliente --}}
    <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-500 mb-1">Cliente</p>
        <p class="font-semibold text-gray-900">{{ $pedido->cliente?->nombre_completo }}</p>
        <p class="text-sm text-gray-500">{{ $pedido->cliente?->correo }}</p>
        <p class="text-sm text-gray-500">C.I. {{ $pedido->cliente?->cedula }}</p>
    </div>

    {{-- Punto de encuentro (si es delivery) --}}
    @if($pedido->metodo_entrega === 'delivery' && $pedido->punto_encuentro)
    <div class="bg-amber-50 rounded-lg p-3">
        <p class="text-xs text-amber-600 mb-1">📍 Punto de entrega</p>
        <p class="text-sm text-gray-900">{{ $pedido->punto_encuentro }}</p>
    </div>
    @endif

    {{-- Repartidor (si está asignado) --}}
    @if($pedido->repartidor)
    <div class="bg-green-50 rounded-lg p-3">
        <p class="text-xs text-green-600 mb-1">🛵 Repartidor asignado</p>
        <p class="font-semibold text-gray-900">{{ $pedido->repartidor->nombre_completo }}</p>
        <p class="text-sm text-gray-500">{{ $pedido->repartidor->facultad }}</p>
    </div>
    @endif

    {{-- Productos --}}
    <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-500 mb-2">Productos</p>
        <div class="space-y-2">
            @foreach($pedido->detalles as $detalle)
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $detalle->producto?->nombre }}</p>
                    <p class="text-xs text-gray-500">{{ $detalle->cantidad }} × ${{ number_format($detalle->precio_unitario, 2) }}</p>
                </div>
                <p class="text-sm font-semibold text-gray-900">${{ number_format($detalle->subtotal, 2) }}</p>
            </div>
            @endforeach
        </div>
        <div class="border-t border-gray-200 mt-2 pt-2 flex justify-between">
            <p class="text-sm font-semibold text-gray-900">Total</p>
            <p class="text-sm font-bold text-green-600">${{ number_format($pedido->total, 2) }}</p>
        </div>
    </div>

    {{-- Comprobante --}}
    @if($pedido->comprobante_pago_url)
    <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-500 mb-2">Comprobante de pago</p>
        <img
            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($pedido->comprobante_pago_url) }}"
            alt="Comprobante"
            class="w-full rounded-lg max-h-64 object-contain"
        />
    </div>
    @endif

    {{-- Fechas --}}
    <div class="text-xs text-gray-400 text-right">
        <p>Creado: {{ $pedido->created_at?->format('d/m/Y H:i') }}</p>
        <p>Actualizado: {{ $pedido->updated_at?->format('d/m/Y H:i') }}</p>
    </div>

</div>
