import React, { useEffect, useState } from 'react';
import {
  View, Text, TouchableOpacity,
  StyleSheet, Alert, ActivityIndicator,
  Image, Image as RNImage,
} from 'react-native';
import * as AuthSession from 'expo-auth-session';
import * as WebBrowser from 'expo-web-browser';
import { useAuth } from '../../store/AuthContext';
import api from '../../services/api';
import { AuthResponse } from '../../types';
import { Colors } from '../../constants/colors';
import {
  MICROSOFT_CLIENT_ID,
  MICROSOFT_DISCOVERY,
  MICROSOFT_SCOPES,
} from '../../constants/microsoftAuth';

// Necesario para que WebBrowser cierre correctamente el flujo de auth
// cuando el usuario vuelve de la pantalla de login de Microsoft.
WebBrowser.maybeCompleteAuthSession();

const redirectUri = AuthSession.makeRedirectUri({ scheme: 'agromarket', path: 'redirect' });

export default function LoginScreen() {
  const { login } = useAuth();
  const [cargando, setCargando] = useState(false);

  const [request, response, promptAsync] = AuthSession.useAuthRequest(
    {
      clientId: MICROSOFT_CLIENT_ID,
      scopes: MICROSOFT_SCOPES,
      redirectUri,
      responseType: AuthSession.ResponseType.Code,
      usePKCE: true,
    },
    MICROSOFT_DISCOVERY
  );

  useEffect(() => {
    if (response?.type === 'success' && request) {
      intercambiarCodigoYLoguear(response.params.code);
    } else if (response?.type === 'error') {
      Alert.alert('Error', 'No se pudo iniciar sesión con Microsoft.');
    }
  }, [response]);

  const intercambiarCodigoYLoguear = async (code: string) => {
    if (!request?.codeVerifier) return;
    setCargando(true);
    try {
      // 1. Cambiamos el código de autorización por un access_token de Microsoft
      const tokenResult = await AuthSession.exchangeCodeAsync(
        {
          clientId: MICROSOFT_CLIENT_ID,
          code,
          redirectUri,
          extraParams: { code_verifier: request.codeVerifier },
        },
        MICROSOFT_DISCOVERY
      );

      // 2. Se lo mandamos a nuestro backend, que valida el token contra
      //    Microsoft Graph y crea/loguea al usuario.
      const backendResponse = await api.post<AuthResponse>('/auth/microsoft', {
        access_token: tokenResult.accessToken,
      });

      await login(backendResponse.data.token, backendResponse.data.usuario);
    } catch (error: any) {
      const mensaje =
        error.response?.data?.message ||
        'No se pudo completar el inicio de sesión con Microsoft.';
      Alert.alert('Error', mensaje);
    } finally {
      setCargando(false);
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.inner}>
        <Image
          source={require('../../../assets/logo.png')}
          style={styles.logo}
          resizeMode="contain"
        />

        <Text style={styles.bienvenida}>Bienvenido</Text>
        <Text style={styles.subtitulo}>
          Usa tu cuenta institucional ULEAM para continuar
        </Text>

        <TouchableOpacity
          style={[styles.boton, (!request || cargando) && styles.botonDeshabilitado]}
          onPress={() => promptAsync()}
          disabled={!request || cargando}
        >
          {cargando ? (
            <ActivityIndicator color={Colors.blanco} />
          ) : (
            <>
              <Text style={styles.botonIcono}>⊞</Text>
              <Text style={styles.botonTexto}>Iniciar sesión con Microsoft</Text>
            </>
          )}
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.fondo },
  inner: { flex: 1, justifyContent: 'center', paddingHorizontal: 32 },
  logo: { width: '100%', height: 180, marginBottom: 24, alignSelf: 'center' },
  bienvenida: {
    fontSize: 26,
    fontWeight: 'bold',
    color: Colors.verde,
    textAlign: 'center',
    marginBottom: 6,
  },
  subtitulo: {
    fontSize: 13,
    color: Colors.grisMedio,
    textAlign: 'center',
    marginBottom: 36,
  },
  boton: {
    flexDirection: 'row',
    gap: 10,
    backgroundColor: Colors.naranja,
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  botonDeshabilitado: { opacity: 0.6 },
  botonIcono: { color: Colors.blanco, fontSize: 18, fontWeight: '700' },
  botonTexto: { color: Colors.blanco, fontSize: 16, fontWeight: '600' },
});