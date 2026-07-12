import React, { createContext, useContext, useEffect, useState, useCallback, useRef } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Notifications from 'expo-notifications';
import { AppState } from 'react-native';
import { Usuario } from '../types';
import { registrarExpoPushToken, limpiarExpoPushToken } from '../services/pushNotifications';
import api from '../services/api';
import { subscribeToUsuarioEvents } from '../services/realtime';

// Event emitter simple para React Native
type PushNavigationEvent = { pedidoId: number; tipo?: string };
type PushNavigationListener = (event: { pedidoId: number; tipo?: string }) => void;

class PushNavigationEmitter {
  private listeners: ((event: { pedidoId: number; tipo?: string }) => void)[] = [];
  // Guardamos el último evento por si todavía no hay un navigationRef listo
  // (ej. la app se abrió recién y NavigationContainer aún no montó/quedó "ready").
  private ultimoEvento: PushNavigationEvent | null = null;

  emit(event: { pedidoId: number; tipo?: string }) {
    this.ultimoEvento = event;
    this.listeners.forEach(listener => listener(event));
  }

  addListener(listener: (event: { pedidoId: number; tipo?: string }) => void) {
    this.listeners.push(listener);
    return () => {
      this.listeners = this.listeners.filter(l => l !== listener);
    };
  }

  consumirUltimoEvento(): PushNavigationEvent | null {
    const evento = this.ultimoEvento;
    this.ultimoEvento = null;
    return evento;
  }
}

export const pushNavigationEmitter = new PushNavigationEmitter();

interface AuthContextType {
  usuario: Usuario | null;
  token: string | null;
  isLoading: boolean;
  login: (token: string, usuario: Usuario) => Promise<void>;
  logout: () => Promise<void>;
  actualizarUsuario: (usuario: Usuario) => void;
  refrescarUsuario: () => Promise<Usuario | null>;
}

const AuthContext = createContext<AuthContextType>({} as AuthContextType);

export const AuthProvider = ({ children }: { children: React.ReactNode }) => {
  const [usuario, setUsuario] = useState<Usuario | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
      cargarSesion();
    }, []);

    // Suscripción WebSocket a eventos del usuario (aprobación/rechazo repartidor)
    // Se ejecuta UNA VEZ al loguearse (cuando token cambia de null a string)
    // Usamos ref para siempre tener el usuario actual sin re-suscribirse
    const usuarioRef = useRef<Usuario | null>(null);
    usuarioRef.current = usuario;

    useEffect(() => {
      if (!token || !usuario?.id) {
        return;
      }

      registrarExpoPushToken(usuario.id);

      // Suscribirse a eventos WebSocket del usuario
      const unsubscribe = subscribeToUsuarioEvents(
        usuario.id,
        () => {
          console.log('[AuthContext] 📡 Recibido evento: repartidor aprobado');
          refrescarUsuario();
        },
        () => {
          console.log('[AuthContext] 📡 Recibido evento: repartidor rechazado');
          refrescarUsuario();
        },
      );

      return () => unsubscribe();
    }, [token]); // Solo depende de token (login/logout), NO de usuario

  // Listener para notificaciones push - navegar a seguimiento del pedido
    useEffect(() => {
      const emitirSiTienePedido = (
        data: Record<string, unknown> | undefined
      ) => {
        if (data?.pedido_id) {
          pushNavigationEmitter.emit({
            pedidoId: Number(data.pedido_id),
            tipo: typeof data.tipo === 'string' ? data.tipo : undefined,
          });
        }
      };

      // Caso normal: la app ya estaba corriendo (foreground/background) y el
      // usuario toca la notificación.
      const subscription = Notifications.addNotificationResponseReceivedListener(response => {
        emitirSiTienePedido(
          response.notification.request.content.data as Record<string, unknown> | undefined
        );
      });

      // Caso "cold start": el proceso estaba matado y la app se abrió
      // directamente al tocar la notificación. addNotificationResponseReceivedListener
      // no siempre alcanza a dispararse a tiempo en este caso, así que revisamos
      // explícitamente cuál fue la última respuesta de notificación.
      Notifications.getLastNotificationResponseAsync().then(response => {
        if (response) {
          emitirSiTienePedido(
            response.notification.request.content.data as Record<string, unknown> | undefined
          );
        }
      });

      return () => subscription.remove();
    }, []);

    // Refrescar usuario automáticamente cuando la app vuelve al primer plano
        useEffect(() => {
          const subscription = AppState.addEventListener('change', nextState => {
            if (nextState === 'active' && token && usuario?.id) {
              refrescarUsuario();
            }
          });

          return () => subscription.remove();
        }, [token]); // Solo depende de token

    const cargarSesion = async () => {
    try {
      const tokenGuardado = await AsyncStorage.getItem('auth_token');
      const usuarioGuardado = await AsyncStorage.getItem('auth_usuario');
      if (tokenGuardado && usuarioGuardado) {
        setToken(tokenGuardado);
        setUsuario(JSON.parse(usuarioGuardado));
      }
    } catch (error) {
      console.error('Error cargando sesión:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const login = useCallback(async (token: string, usuario: Usuario) => {
      await AsyncStorage.setItem('auth_token', token);
      await AsyncStorage.setItem('auth_usuario', JSON.stringify(usuario));
      setToken(token);
      setUsuario(usuario);
    }, []);

    const logout = useCallback(async () => {
      if (usuario?.id) {
        await limpiarExpoPushToken(usuario.id);
      }
      await AsyncStorage.removeItem('auth_token');
      await AsyncStorage.removeItem('auth_usuario');
      setToken(null);
      setUsuario(null);
    }, [usuario?.id]);

    const actualizarUsuario = useCallback((usuario: Usuario) => {
        setUsuario(usuario);
        AsyncStorage.setItem('auth_usuario', JSON.stringify(usuario));
      }, []);

      // Refresca el usuario desde el backend y actualiza AsyncStorage
          const refrescarUsuario = useCallback(async () => {
            if (!token) return;
            try {
              const response = await api.get('/auth/me');
              const usuarioActualizado = response.data;
              setUsuario(usuarioActualizado);
              await AsyncStorage.setItem('auth_usuario', JSON.stringify(usuarioActualizado));
              return usuarioActualizado;
            } catch (error) {
              console.error('Error refrescando usuario:', error);
              // Si falla por 401, el interceptor ya limpia el token
              return null;
            }
          }, [token]); // Solo depende de token

    return (
      <AuthContext.Provider
        value={{ usuario, token, isLoading, login, logout, actualizarUsuario, refrescarUsuario }}
      >
        {children}
      </AuthContext.Provider>
    );
  };

export const useAuth = () => useContext(AuthContext);