<div wire:poll.5000ms="checkPedidos"></div>

@once
    <script>
        document.addEventListener('livewire:init', () => {
            if (window.__adminPedidosWatcherInitialized) {
                return;
            }

            window.__adminPedidosWatcherInitialized = true;

            Livewire.on('pedidos-count-updated', (event) => {
                const count = Number(event?.count ?? 0);
                updatePedidosBadge(count);
            });
        });

        function updatePedidosBadge(count) {
            const pedidosLink = Array.from(document.querySelectorAll('a')).find((anchor) =>
                anchor.href.includes('/admin/pedidos')
            );

            if (!pedidosLink) {
                return;
            }

            let badge = pedidosLink.querySelector('[data-pedidos-badge]');

            if (count <= 0) {
                if (badge) {
                    badge.remove();
                }

                return;
            }

            if (!badge) {
                badge = document.createElement('span');
                badge.setAttribute('data-pedidos-badge', 'true');
                badge.style.cssText = [
                    'margin-left:auto',
                    'display:inline-flex',
                    'align-items:center',
                    'justify-content:center',
                    'min-width:20px',
                    'height:20px',
                    'padding:0 6px',
                    'border-radius:9999px',
                    'background:#f59e0b',
                    'color:#111827',
                    'font-size:12px',
                    'font-weight:700',
                    'line-height:1',
                ].join(';');

                pedidosLink.appendChild(badge);
            }

            badge.textContent = String(count);
        }
    </script>
@endonce
