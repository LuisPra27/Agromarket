import React, { createContext, useContext, useEffect, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Usuario } from '../types';

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