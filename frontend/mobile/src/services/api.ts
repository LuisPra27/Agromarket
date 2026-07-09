import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';

const FALLBACK_API_URL = 'http://10.82.24.219:8000';

if (!process.env.EXPO_PUBLIC_API_URL) {
  // Si ves esto en los logs de un build release, significa que el .env NO
  // se leyó al empacar el JS (por ejemplo: se compiló sin correr antes
  // `.\scripts\dev.ps1 set-ip`, o Gradle cacheó un bundle viejo). La app
  // va a intentar hablar con una IP vieja/incorrecta y ninguno de los
  // datos que veas va a ser confiable hasta que se recompile con la IP
  // correcta en el .env.
  console.warn(
    `[api] EXPO_PUBLIC_API_URL no está definida — usando fallback ${FALLBACK_API_URL}. ` +
    'Si esto es un build release, corre ".\\scripts\\dev.ps1 set-ip" y recompila con gradle clean antes de generar el APK.'
  );
}

export const API_URL = process.env.EXPO_PUBLIC_API_URL ?? FALLBACK_API_URL;


const api = axios.create({
  baseURL: `${API_URL}/api`,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

api.interceptors.request.use(async (config) => {
  const token = await AsyncStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      await AsyncStorage.removeItem('auth_token');
      await AsyncStorage.removeItem('auth_usuario');
    }
    return Promise.reject(error);
  }
);

export default api;