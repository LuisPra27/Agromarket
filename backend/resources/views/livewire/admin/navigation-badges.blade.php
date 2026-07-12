<div wire:ignore class="hidden" id="navigation-badges-data">
    <span id="badge-pedidos-pendientes" data-count="{{ $pendientesValidacion }}"></span>
    <span id="badge-en-delivery" data-count="{{ $enDelivery }}"></span>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', () => {
        Livewire => {
            Livewire.on('navigationBadgesUpdated', ({ pendientesValidacion, enDelivery }) => {
                // Actualizar badge "Caja / Validación"
                const badgeCaja = document.querySelector('[data-filament-navigation-item="caja-validacion"] .fi-badge');
                if (badgeCaja) {
                    if (pendientesValidacion > 0) {
                        badgeCaja.textContent = pendientesValidacion;
                        badgeCaja.style.display = 'inline-flex';
                    } else {
                        badgeCaja.style.display = 'none';
                    }
                }

                // Actualizar badge "Delivery"
                const badgeDelivery = document.querySelector('[data-filament-navigation-item="delivery"] .fi-badge');
                if (badgeDelivery) {
                    if (enDelivery > 0) {
                        badgeDelivery.textContent = enDelivery;
                        badgeDelivery.style.display = 'inline-flex';
                    } else {
                        badgeDelivery.style.display = 'none';
                    }
                }
            });
        });
</script>
@endpush