import Echo from 'laravel-echo';
import { PedidoListoParaDeliveryEvent } from '../types';

// pusher-js puede llegar envuelto por Metro como { default: Pusher, ... }
// en vez de exponer la clase directamente. Cubrimos ambos casos.
const PusherModule = require('pusher-js');
const Pusher = PusherModule?.default ?? PusherModule?.Pusher ?? PusherModule;

if (typeof Pusher !== 'function') {
  throw new Error(
    'No se pudo resolver el constructor de pusher-js. Revisa la versión instalada o cómo Metro está empaquetando el módulo.'
  );
}

const WS_HOST = process.env.EXPO_PUBLIC_WS_HOST ?? '192.168.100.13';

const ECHO_OPTIONS = {
  wsHost: WS_HOST,
  wsPort: 8080,
  wssPort: 8080,
  forceTLS: false,
  enabledTransports: ['ws'] as const,
};

// 3. Laravel Echo espera una INSTANCIA de Pusher en `client`, no la clase.
//    Si le pasamos la clase directamente, Echo hace `this.pusher = this.options.client`
//    sin instanciarla, y luego `this.pusher.subscribe(...)` falla porque `subscribe`
//    no existe como método estático de la clase (causaba "undefined is not a function").
const pusherClient = new Pusher('agromarket-key', {
  ...ECHO_OPTIONS,
  // pusher-js exige `cluster` aunque no se use: al usar Reverb (self-hosted)
  // la conexión real va por `wsHost`, este valor solo satisface la validación interna.
  // (No se lo pasamos a Echo: sus tipos para el broadcaster 'reverb' no aceptan 'cluster'.)
  cluster: 'mt1',
});

const echo = new Echo({
  broadcaster: 'reverb',
  client: pusherClient, // <-- ahora sí, una instancia real
  key: 'agromarket-key',
  ...ECHO_OPTIONS,
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