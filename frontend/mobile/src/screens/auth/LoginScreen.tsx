import React, { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity,
  StyleSheet, Alert, ActivityIndicator,
  KeyboardAvoidingView, Platform, Image,
} from 'react-native';
import { useAuth } from '../../store/AuthContext';
import api from '../../services/api';
import { AuthResponse } from '../../types';
import { Colors } from '../../constants/colors';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';

export default function LoginScreen() {
  const { login } = useAuth();
  const [correo, setCorreo] = useState('');
  const [clave, setClave] = useState('');
  const [verClave, setVerClave] = useState(false);
  const [cargando, setCargando] = useState(false);
  const navigation = useNavigation<any>();

  const handleLogin = async () => {
    if (!correo || !clave) {
      Alert.alert('Error', 'Por favor ingresa tu correo y contraseña.');
      return;
    }
    setCargando(true);
    try {
      const response = await api.post<AuthResponse>('/auth/login', { correo, clave });
      await login(response.data.token, response.data.usuario);
    } catch (error: any) {
      const mensaje =
        error.response?.data?.message ||
        error.response?.data?.errors?.correo?.[0] ||
        'Error al iniciar sesión. Verifica tus credenciales.';
      Alert.alert('Error', mensaje);
    } finally {
      setCargando(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <View style={styles.inner}>
        {/* Logo */}
        <Image
          source={require('../../../assets/logo.png')}
          style={styles.logo}
          resizeMode="contain"
        />

        <Text style={styles.bienvenida}>Bienvenido</Text>
        <Text style={styles.subtitulo}>Inicia sesión con tu correo institucional</Text>

        <TextInput
          style={styles.input}
          placeholder="Correo institucional"
          placeholderTextColor={Colors.grisMedio}
          value={correo}
          onChangeText={setCorreo}
          keyboardType="email-address"
          autoCapitalize="none"
          autoCorrect={false}
        />

        <View style={styles.inputWrapper}>
          <TextInput
            style={[styles.input, styles.inputWithIcon]}
            placeholder="Contraseña"
            placeholderTextColor={Colors.grisMedio}
            value={clave}
            onChangeText={setClave}
            secureTextEntry={!verClave}
            autoCapitalize="none"
            autoCorrect={false}
          />
          <TouchableOpacity
            style={styles.iconBtn}
            onPress={() => setVerClave(!verClave)}
            hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
          >
            <Ionicons
              name={verClave ? 'eye-off-outline' : 'eye-outline'}
              size={22}
              color={Colors.grisMedio}
            />
          </TouchableOpacity>
        </View>

        <TouchableOpacity
          style={[styles.boton, cargando && styles.botonDeshabilitado]}
          onPress={handleLogin}
          disabled={cargando}
        >
          {cargando ? (
            <ActivityIndicator color={Colors.blanco} />
          ) : (
            <Text style={styles.botonTexto}>Iniciar sesión</Text>
          )}
        </TouchableOpacity>
        <TouchableOpacity
          style={styles.btnRegistro}
          onPress={() => navigation.navigate('Register' as never)}
        >
          <Text style={styles.btnRegistroTexto}>
            ¿No tienes cuenta? <Text style={styles.btnRegistroEnfasis}>Regístrate aquí</Text>
          </Text>
        </TouchableOpacity>
      </View>
    </KeyboardAvoidingView>
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
  input: {
    backgroundColor: Colors.blanco,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 15,
    color: Colors.negro,
    marginBottom: 16,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.blanco,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    borderRadius: 12,
    marginBottom: 16,
  },
  inputWithIcon: {
    flex: 1,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 15,
    color: Colors.negro,
    marginBottom: 0,
  },
  iconBtn: {
    padding: 12,
    paddingRight: 16,
  },
  boton: {
    backgroundColor: Colors.verde,
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: 8,
  },
  botonDeshabilitado: { opacity: 0.6 },
  botonTexto: { color: Colors.blanco, fontSize: 16, fontWeight: '600' },
  btnRegistro: { alignItems: 'center', paddingVertical: 16 },
  btnRegistroTexto: { fontSize: 14, color: Colors.grisMedio },
  btnRegistroEnfasis: { color: Colors.verde, fontWeight: '600' },
});