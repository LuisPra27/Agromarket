import React, { createContext, useContext, useEffect, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Notifications from 'expo-notifications';
import { Usuario } from '../types';
import { registrarExpoPushToken, limpiarExpoPushToken } from '../services/pushNotifications';
import api from './api';

// Event emitter simple para React Native
type PushNavigationEvent = { pedidoId: number; tipo?: string };
type PushNavigationListener = (event: { pedidoId: number; tipo?: string }) => void;

class PushNavigationEmitter {
  private listeners: ((event: { pedidoId: number; tipo?: string }) => void)[] = [];

  emit(event: { pedidoId: number; tipo?: string }) {
    this.listeners.forEach(listener => listener(event));
  }

  addListener(listener: (event: { pedidoId: number; tipo?: string }) => void) {
    this.listeners.push(listener);
    return () => {
      this.listeners = this.listeners.filter(l => l !== listener);
    };
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
}

const AuthContext = createContext<AuthContextType>({} as AuthContextType);

export const AuthProvider = ({ children }: { children: React.ReactNode }) => {
  const [usuario, setUsuario] = useState<Usuario | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    cargarSesion();
  }, []);

  useEffect(() => {
    if (!token || !usuario?.id) {
      return;
    }

    registrarExpoPushToken(usuario.id);
  }, [token, usuario?.id]);

  // Listener para notificaciones push - navegar a seguimiento del pedido
  useEffect(() => {
    const subscription = Notifications.addNotificationResponseReceivedListener(response => {
      const data = response.notification.request.content.data as Record<string, unknown> | undefined;
      if (data?.pedido_id) {
        // Emitir evento para que AppNavigator navegue
        pushNavigationEmitter.emit({
          pedidoId: Number(data.pedido_id),
          tipo: typeof data.tipo === 'string' ? data.tipo : undefined,
        });
      }
    });

    return () => subscription.remove();
  }, []);

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

  const login = async (token: string, usuario: Usuario) => {
    await AsyncStorage.setItem('auth_token', token);
    await AsyncStorage.setItem('auth_usuario', JSON.stringify(usuario));
    setToken(token);
    setUsuario(usuario);
  };

  const logout = async () => {
    if (usuario?.id) {
      await limpiarExpoPushToken(usuario.id);
    }
    await AsyncStorage.removeItem('auth_token');
    await AsyncStorage.removeItem('auth_usuario');
    setToken(null);
    setUsuario(null);
  };

  const actualizarUsuario = (usuario: Usuario) => {
    setUsuario(usuario);
    AsyncStorage.setItem('auth_usuario', JSON.stringify(usuario));
  };

  return (
    <AuthContext.Provider
      value={{ usuario, token, isLoading, login, logout, actualizarUsuario }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);