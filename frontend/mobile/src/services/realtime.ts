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

const FALLBACK_WS_HOST = '192.168.100.13';

if (!process.env.EXPO_PUBLIC_WS_HOST) {
  // Mismo problema que con EXPO_PUBLIC_API_URL: si ves esto en un build
  // release, el .env no se leyó al empacar el JS. El WebSocket va a
  // intentar conectarse a una IP vieja y fallar en silencio (la app no
  // se rompe, simplemente nunca recibe eventos en tiempo real).
  console.warn(
    `[realtime] EXPO_PUBLIC_WS_HOST no está definida — usando fallback ${FALLBACK_WS_HOST}. ` +
    'Si esto es un build release, corre ".\\scripts\\dev.ps1 set-ip" y recompila con gradle clean antes de generar el APK.'
  );
}

export const WS_HOST = process.env.EXPO_PUBLIC_WS_HOST ?? FALLBACK_WS_HOST;

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

// Logging de estado de conexión: una conexión WS fallida (IP incorrecta,
// Reverb caído, firewall, etc.) NO lanza ningún error visible en la UI —
// simplemente el usuario nunca recibe eventos en tiempo real. Estos logs
// son la única forma de darse cuenta sin instrumentación adicional.
pusherClient.connection.bind('connected', () => {
  console.log(`[realtime] ✅ Conectado a Reverb en ${WS_HOST}:8080`);
});
pusherClient.connection.bind('error', (err: unknown) => {
  console.warn(`[realtime] ❌ Error de conexión a Reverb (${WS_HOST}:8080):`, err);
});
pusherClient.connection.bind('unavailable', () => {
  console.warn(`[realtime] ⚠️ Reverb no disponible en ${WS_HOST}:8080 (¿está corriendo el contenedor? ¿IP correcta?)`);
});

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