import Echo from 'laravel-echo';
import Pusher from 'pusher-js/react-native';
import { API_URL } from './api';
import { PedidoListoParaDeliveryEvent } from '../types';

const globalPusher = globalThis as typeof globalThis & { Pusher?: typeof Pusher };

if (!globalPusher.Pusher) {
  globalPusher.Pusher = Pusher;
}

if (typeof window !== 'undefined') {
  (window as typeof window & { Pusher?: typeof Pusher }).Pusher = Pusher;
}

Pusher.logToConsole = __DEV__;

let echoInstance: Echo<any> | null = null;

function getEchoInstance() {
  if (echoInstance) {
    return echoInstance;
  }

  const { hostname } = new URL(API_URL);

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key: 'agromarket-key',
    wsHost: hostname,
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws'],
    disableStats: true,
  });

  return echoInstance;
}

export function subscribeToPedidosRepartidores(
  onPedidoListo: (pedido: PedidoListoParaDeliveryEvent) => void,
) {
  const echo = getEchoInstance();
  const channel = echo.channel('repartidores');

  channel.listen('.pedido.listo', onPedidoListo);

  return () => {
    channel.stopListening('.pedido.listo');
    echo.leaveChannel('repartidores');
  };
}