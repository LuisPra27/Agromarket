import Echo from 'laravel-echo';
import { PedidoListoParaDeliveryEvent } from '../types';

// 1. ELIMINAMOS el "import Pusher from 'pusher-js'"
// 2. Usamos require() para obtener el constructor directamente, sin que Metro lo envuelva en objetos raros.
const Pusher = require('pusher-js');

const WS_HOST = process.env.EXPO_PUBLIC_WS_HOST ?? '192.168.100.13';

const echo = new Echo({
  broadcaster: 'reverb',
  client: Pusher, // <-- 3. Ahora sí, le pasamos la clase constructora real a Echo
  key: 'agromarket-key',
  wsHost: WS_HOST,
  wsPort: 8080,
  wssPort: 8080,
  forceTLS: false,
  enabledTransports: ['ws'],
});

export default echo;

/**
 * Función para suscribirse al canal de repartidores
 */
export function subscribeToPedidosRepartidores(
  onPedidoListo: (pedido: PedidoListoParaDeliveryEvent) => void,
) {
  const channel = echo.channel('repartidores');

  channel.listen('.pedido.listo', (e: PedidoListoParaDeliveryEvent) => {
    onPedidoListo(e);
  });

  return () => {
    channel.stopListening('.pedido.listo');
  };
}