import React, { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  Alert, ActivityIndicator, KeyboardAvoidingView, Platform,
} from 'react-native';
import { useAuth } from '../../store/AuthContext';
import api from '../../services/api';
import { Usuario } from '../../types';
import { Colors } from '../../constants/colors';

// Se muestra una sola vez, justo después del primer login con Microsoft,
// cuando el usuario todavía no tiene cédula guardada (la BD la sigue
// necesitando para el resto de la lógica de la app).
export default function CompletarPerfilScreen() {
  const { actualizarUsuario, usuario } = useAuth();
  const [cedula, setCedula] = useState('');
  const [cargando, setCargando] = useState(false);

  const handleContinuar = async () => {
    if (cedula.length !== 10) {
      Alert.alert('Error', 'La cédula debe tener exactamente 10 dígitos.');
      return;
    }
    setCargando(true);
    try {
      const response = await api.post<{ usuario: Usuario }>('/auth/completar-perfil', {
        cedula,
      });
      actualizarUsuario(response.data.usuario);
    } catch (error: any) {
      const errores = error.response?.data?.errors;
      const mensaje = errores
        ? (Object.values(errores)[0] as string[])[0]
        : error.response?.data?.message ?? 'No se pudo guardar la cédula.';
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
        <Text style={styles.titulo}>Un último paso</Text>
        <Text style={styles.subtitulo}>
          {usuario?.nombre_completo ? `Hola, ${usuario.nombre_completo.split(' ')[0]}. ` : ''}
          Necesitamos tu cédula para poder validar tus pedidos y pagos.
        </Text>

        <TextInput
          style={styles.input}
          placeholder="Número de cédula (sin guión)"
          placeholderTextColor={Colors.grisMedio}
          value={cedula}
          onChangeText={v => setCedula(v.replace(/\D/g, '').slice(0, 10))}
          keyboardType="numeric"
          maxLength={10}
        />

        <TouchableOpacity
          style={[styles.boton, (cargando || cedula.length !== 10) && styles.botonDeshabilitado]}
          onPress={handleContinuar}
          disabled={cargando || cedula.length !== 10}
        >
          {cargando ? (
            <ActivityIndicator color={Colors.blanco} />
          ) : (
            <Text style={styles.botonTexto}>Continuar</Text>
          )}
        </TouchableOpacity>
      </View>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.fondo },
  inner: { flex: 1, justifyContent: 'center', paddingHorizontal: 32 },
  titulo: {
    fontSize: 24,
    fontWeight: 'bold',
    color: Colors.verde,
    textAlign: 'center',
    marginBottom: 10,
  },
  subtitulo: {
    fontSize: 14,
    color: Colors.grisMedio,
    textAlign: 'center',
    marginBottom: 28,
    lineHeight: 20,
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
    textAlign: 'center',
    letterSpacing: 1,
  },
  boton: {
    backgroundColor: Colors.verde,
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: 'center',
  },
  botonDeshabilitado: { opacity: 0.5 },
  botonTexto: { color: Colors.blanco, fontSize: 16, fontWeight: '600' },
});