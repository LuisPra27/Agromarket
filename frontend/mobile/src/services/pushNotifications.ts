import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import * as Notifications from 'expo-notifications';
import api from './api';

function storageKeyForUser(usuarioId: number) {
  return `expo_push_token_registrado:${usuarioId}`;
}

async function obtenerExpoPushToken() {
  const { status } = await Notifications.getPermissionsAsync();
  let finalStatus = status;

  if (status !== 'granted') {
    const response = await Notifications.requestPermissionsAsync();
    finalStatus = response.status;
  }

  if (finalStatus !== 'granted') {
    return null;
  }

  const projectId = Constants.expoConfig?.extra?.eas?.projectId;

  try {
    const tokenResponse = projectId
      ? await Notifications.getExpoPushTokenAsync({ projectId })
      : await Notifications.getExpoPushTokenAsync();

    return tokenResponse.data;
  } catch (error) {
    console.warn('No se pudo obtener el Expo push token:', error);
    return null;
  }
}

export async function registrarExpoPushToken(usuarioId: number) {
  try {
    const token = await obtenerExpoPushToken();
    if (!token) {
      return;
    }

    const storageKey = storageKeyForUser(usuarioId);
    const tokenRegistrado = await AsyncStorage.getItem(storageKey);
    if (tokenRegistrado === token) {
      return;
    }

    await api.post('/auth/push-token', {
      expo_push_token: token,
    });

    await AsyncStorage.setItem(storageKey, token);
  } catch (error) {
    console.warn('No se pudo registrar el Expo push token:', error);
  }
}