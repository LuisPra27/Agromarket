import React, { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  Alert, ActivityIndicator, ScrollView, KeyboardAvoidingView, Platform,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '../../store/AuthContext';
import api from '../../services/api';
import { AuthResponse } from '../../types';
import { Colors } from '../../constants/colors';
import { Ionicons } from '@expo/vector-icons';

export default function RegisterScreen() {
  const navigation = useNavigation<any>();
  const { login } = useAuth();

  const [cedula, setCedula] = useState('');
  const [nombreCompleto, setNombreCompleto] = useState('');
  const [clave, setClave] = useState('');
  const [claveConfirmacion, setClaveConfirmacion] = useState('');
  const [verClave, setVerClave] = useState(false);
  const [verClaveConfirmacion, setVerClaveConfirmacion] = useState(false);
  const [cargando, setCargando] = useState(false);

  // El correo se auto-genera desde la cédula
  const correoGenerado = cedula.length === 10 ? `e${cedula}@live.uleam.edu.ec` : '';

  const handleRegister = async () => {
    if (!cedula || cedula.length !== 10) {
      Alert.alert('Error', 'La cédula debe tener exactamente 10 dígitos.');
      return;
    }
    if (!nombreCompleto.trim()) {
      Alert.alert('Error', 'Por favor ingresa tu nombre completo.');
      return;
    }
    if (clave.length < 6) {
      Alert.alert('Error', 'La contraseña debe tener al menos 6 caracteres.');
      return;
    }
    if (clave !== claveConfirmacion) {
      Alert.alert('Error', 'Las contraseñas no coinciden.');
      return;
    }

    setCargando(true);
    try {
      const response = await api.post<AuthResponse>('/auth/register', {
        cedula,
        nombre_completo: nombreCompleto.trim(),
        correo: correoGenerado,
        clave,
        clave_confirmation: claveConfirmacion,
      });

      await login(response.data.token, response.data.usuario);
    } catch (error: any) {
      const errores = error.response?.data?.errors;
      if (errores) {
        const primerError = Object.values(errores)[0] as string[];
        Alert.alert('Error', primerError[0]);
      } else {
        Alert.alert('Error', error.response?.data?.message ?? 'No se pudo crear la cuenta.');
      }
    } finally {
      setCargando(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView contentContainerStyle={styles.inner} keyboardShouldPersistTaps="handled">

        <Text style={styles.titulo}>Crear cuenta</Text>
        <Text style={styles.subtitulo}>Ingresa tu cédula</Text>

        {/* Cédula */}
        <View style={styles.campo}>
          <Text style={styles.label}>Cédula de identidad</Text>
          <TextInput
            style={styles.input}
            placeholder="Número de cédula (sin guión)"
            placeholderTextColor={Colors.grisMedio}
            value={cedula}
            onChangeText={v => setCedula(v.replace(/\D/g, '').slice(0, 10))}
            keyboardType="numeric"
            maxLength={10}
          />
        </View>

        {/* Correo auto-generado */}
        {correoGenerado ? (
          <View style={styles.correoPreview}>
            <Text style={styles.correoLabel}>✅ Tu correo institucional:</Text>
            <Text style={styles.correoValor}>{correoGenerado}</Text>
          </View>
        ) : (
          <View style={styles.correoPreview}>
            <Text style={styles.correoHint}>
              💡 Ingresa tu cédula para ver tu correo institucional
            </Text>
          </View>
        )}

        {/* Nombre completo */}
        <View style={styles.campo}>
          <Text style={styles.label}>Nombre completo</Text>
          <TextInput
            style={styles.input}
            placeholder="Nombres y Apellidos"
            placeholderTextColor={Colors.grisMedio}
            value={nombreCompleto}
            onChangeText={setNombreCompleto}
            autoCapitalize="words"
          />
        </View>

        {/* Contraseña */}
        <View style={styles.campo}>
          <Text style={styles.label}>Contraseña</Text>
          <View style={styles.inputWrapper}>
            <TextInput
              style={[styles.input, styles.inputWithIcon]}
              placeholder="Mínimo 6 caracteres"
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
        </View>

        {/* Confirmar contraseña */}
        <View style={styles.campo}>
          <Text style={styles.label}>Confirmar contraseña</Text>
          <View style={styles.inputWrapper}>
            <TextInput
              style={[styles.input, styles.inputWithIcon,
                claveConfirmacion && clave !== claveConfirmacion && styles.inputError]}
              placeholder="Repite tu contraseña"
              placeholderTextColor={Colors.grisMedio}
              value={claveConfirmacion}
              onChangeText={setClaveConfirmacion}
              secureTextEntry={!verClaveConfirmacion}
              autoCapitalize="none"
              autoCorrect={false}
            />
            <TouchableOpacity
              style={styles.iconBtn}
              onPress={() => setVerClaveConfirmacion(!verClaveConfirmacion)}
              hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
            >
              <Ionicons
                name={verClaveConfirmacion ? 'eye-off-outline' : 'eye-outline'}
                size={22}
                color={Colors.grisMedio}
              />
            </TouchableOpacity>
          </View>
          {claveConfirmacion && clave !== claveConfirmacion && (
            <Text style={styles.errorTexto}>Las contraseñas no coinciden</Text>
          )}
        </View>

        {/* Botón registrar */}
        <TouchableOpacity
          style={[styles.boton, (cargando || !correoGenerado) && styles.botonDeshabilitado]}
          onPress={handleRegister}
          disabled={cargando || !correoGenerado}
        >
          {cargando ? (
            <ActivityIndicator color={Colors.blanco} />
          ) : (
            <Text style={styles.botonTexto}>Crear cuenta</Text>
          )}
        </TouchableOpacity>

        {/* Volver al login */}
        <TouchableOpacity
          style={styles.btnVolver}
          onPress={() => navigation.goBack()}
        >
          <Text style={styles.btnVolverTexto}>¿Ya tienes cuenta? Inicia sesión</Text>
        </TouchableOpacity>

      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.fondo },
  inner: { padding: 24, paddingTop: 40, gap: 12 },
  titulo: { fontSize: 28, fontWeight: 'bold', color: Colors.verde, marginBottom: 4 },
  subtitulo: { fontSize: 13, color: Colors.grisMedio, lineHeight: 18, marginBottom: 8 },
  campo: { gap: 6 },
  label: { fontSize: 13, fontWeight: '600', color: Colors.grisOscuro },
  input: {
    backgroundColor: Colors.blanco,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 15,
    color: Colors.negro,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.blanco,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    borderRadius: 12,
  },
  inputWithIcon: {
    flex: 1,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 15,
    color: Colors.negro,
  },
  iconBtn: {
    padding: 12,
    paddingRight: 16,
  },
  inputError: { borderColor: '#ef4444' },
  errorTexto: { fontSize: 12, color: '#ef4444', marginTop: 2 },
  correoPreview: {
    backgroundColor: Colors.blanco,
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    gap: 4,
  },
  correoLabel: { fontSize: 12, color: Colors.verde, fontWeight: '600' },
  correoValor: { fontSize: 15, color: Colors.negro, fontWeight: '500' },
  correoHint: { fontSize: 13, color: Colors.grisMedio },
  boton: {
    backgroundColor: Colors.verde,
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: 8,
  },
  botonDeshabilitado: { opacity: 0.5 },
  botonTexto: { color: Colors.blanco, fontSize: 16, fontWeight: '600' },
  btnVolver: { alignItems: 'center', paddingVertical: 12 },
  btnVolverTexto: { color: Colors.verde, fontSize: 14 },
});