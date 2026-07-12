import React, { useState, useEffect } from 'react';
import {
  View, Text, StyleSheet, TouchableOpacity,
  Alert, ScrollView, TextInput, ActivityIndicator, RefreshControl,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '../../store/AuthContext';
import api, { API_URL } from '../../services/api';
import { WS_HOST } from '../../services/realtime';
import { Colors } from '../../constants/colors';

export default function PerfilScreen() {
  const navigation = useNavigation<any>();
    const { usuario, logout, actualizarUsuario, refrescarUsuario } = useAuth();
    const [cargando, setCargando] = useState(false);
    const [refreshing, setRefreshing] = useState(false);
    const [facultad, setFacultad] = useState(usuario?.facultad ?? '');
    const [postulando, setPostulando] = useState(false);

    // Pull-to-refresh: llama directamente al método estabilizado del contexto
    const onRefresh = () => {
      setRefreshing(true);
      refrescarUsuario().finally(() => setRefreshing(false));
    };

    // Refrescar al montar la pantalla (cuando el usuario navega a ella)
    useEffect(() => {
      refrescarUsuario();
    }, [refrescarUsuario]);

  const handleLogout = async () => {
    Alert.alert('Cerrar sesión', '¿Estás seguro?', [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Cerrar sesión',
        style: 'destructive',
        onPress: async () => {
          try {
            await api.post('/auth/logout');
          } catch (e) {
            // Si falla el logout del servidor igual limpiamos local
          } finally {
            await logout();
          }
        },
      },
    ]);
  };

  const handlePostular = async () => {
    if (!facultad.trim()) {
      Alert.alert('Falta la facultad', 'Debes indicar tu facultad para postular como repartidor.');
      return;
    }

    Alert.alert(
      'Postular como repartidor',
      `Postularás con la facultad: ${facultad.trim()}\n\nEl administrador revisará tu solicitud.`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Confirmar',
          onPress: async () => {
            setPostulando(true);
            try {
              const response = await api.post('/auth/postular-repartidor', {
                facultad: facultad.trim(),
              });
              actualizarUsuario(response.data.usuario);
              Alert.alert('¡Solicitud enviada!', 'El administrador revisará tu postulación y recibirás una respuesta pronto.');
            } catch (error: any) {
              Alert.alert('Error', error.response?.data?.message ?? 'No se pudo enviar la solicitud.');
            } finally {
              setPostulando(false);
            }
          },
        },
      ]
    );
  };

  const estadoRepartidorConfig = {
    no_postulado: { label: 'No postulado', color: Colors.grisMedio },
    pendiente: { label: '⏳ Solicitud en revisión', color: '#f59e0b' },
    aprobado: { label: '✅ Repartidor aprobado', color: Colors.verde },
    rechazado: { label: '❌ Solicitud rechazada', color: '#ef4444' },
  };

  const estadoConfig = estadoRepartidorConfig[usuario?.estado_repartidor ?? 'no_postulado'];

  return (
      <ScrollView
        style={styles.container}
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >

      {/* Avatar y nombre */}
      <View style={styles.header}>
        <View style={styles.avatar}>
          <Text style={styles.avatarTexto}>
            {usuario?.nombre_completo?.charAt(0)?.toUpperCase() ?? 'U'}
          </Text>
        </View>
        <Text style={styles.nombre}>{usuario?.nombre_completo}</Text>
        <Text style={styles.correo}>{usuario?.correo}</Text>
        <Text style={styles.cedula}>C.I. {usuario?.cedula}</Text>
      </View>

      {/* Info del usuario */}
            <View style={styles.seccion}>
              <Text style={styles.seccionTitulo}>Información de cuenta</Text>
              <View style={styles.infoFila}>
                <Text style={styles.infoLabel}>Rol</Text>
                <Text style={styles.infoValor}>{usuario?.rol}</Text>
              </View>
              {usuario?.facultad && (
                <View style={styles.infoFila}>
                  <Text style={styles.infoLabel}>Facultad</Text>
                  <Text style={styles.infoValor}>{usuario.facultad}</Text>
                </View>
              )}
              {usuario?.telefono && (
                <View style={styles.infoFila}>
                  <Text style={styles.infoLabel}>Teléfono</Text>
                  <Text style={styles.infoValor}>{usuario.telefono}</Text>
                </View>
              )}
              <View style={styles.infoFila}>
                <Text style={styles.infoLabel}>Balance delivery</Text>
                <Text style={[styles.infoValor, { color: Colors.verde, fontWeight: '700' }]}>
                  ${Number(usuario?.balance ?? 0).toFixed(2)}
                </Text>
              </View>
            </View>

      {/* Estado de repartidor */}
      <View style={styles.seccion}>
        <Text style={styles.seccionTitulo}>Estado como repartidor</Text>
        <View style={[styles.estadoBadge, { backgroundColor: estadoConfig.color + '20' }]}>
          <Text style={[styles.estadoTexto, { color: estadoConfig.color }]}>
            {estadoConfig.label}
          </Text>
        </View>

        {/* Postular */}
        {(usuario?.estado_repartidor === 'no_postulado' || usuario?.estado_repartidor === 'rechazado') && (
          <View style={styles.postularContainer}>
            <Text style={styles.postularDesc}>
              {usuario.estado_repartidor === 'rechazado'
                ? 'Tu solicitud anterior fue rechazada. Puedes volver a intentarlo.'
                : '¿Quieres ganar dinero entregando pedidos en el campus? ¡Postúlate como repartidor!'}
            </Text>
            <TextInput
              style={styles.input}
              placeholder="Tu facultad (ej: Facultad de Ingeniería)"
              placeholderTextColor={Colors.grisMedio}
              value={facultad}
              onChangeText={setFacultad}
            />
            <TouchableOpacity
              style={[styles.btnPostular, postulando && styles.btnDeshabilitado]}
              onPress={handlePostular}
              disabled={postulando}
            >
              {postulando ? (
                <ActivityIndicator color={Colors.blanco} />
              ) : (
                <Text style={styles.btnPostularTexto}>🛵 Postular como repartidor</Text>
              )}
            </TouchableOpacity>
          </View>
        )}

        {usuario?.estado_repartidor === 'pendiente' && (
          <Text style={styles.pendienteTexto}>
            Tu solicitud está siendo revisada por el administrador. Te notificaremos cuando haya una respuesta.
          </Text>
        )}

        {usuario?.estado_repartidor === 'aprobado' && (
          <View style={{ gap: 8 }}>
            <Text style={styles.aprobadoTexto}>
              Eres repartidor activo. Entra al modo repartidor para ver los viajes disponibles.
            </Text>
            <TouchableOpacity
              style={styles.btnRepartidor}
              onPress={() => navigation.navigate('RepartidorTabs')}
            >
              <Text style={styles.btnRepartidorTexto}>🛵 Ir al modo repartidor</Text>
            </TouchableOpacity>
          </View>
        )}
      </View>

      {/* Cerrar sesión */}
      <TouchableOpacity style={styles.btnLogout} onPress={handleLogout}>
        <Text style={styles.btnLogoutTexto}>Cerrar sesión</Text>
      </TouchableOpacity>

      {/* Indicador de backend: útil para confirmar a qué servidor está
          apuntando un build release (el .env se empaqueta en el momento
          de compilar, así que esto puede quedar "congelado" en una IP
          vieja si no se corrió dev.ps1 set-ip antes de compilar). */}
      <Text style={styles.backendDebug}>Backend: {API_URL}</Text>
      <Text style={styles.backendDebug}>WebSocket: {WS_HOST}:8080</Text>

    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.fondo },
  content: { padding: 16, gap: 16, paddingBottom: 40 },
  header: {
    backgroundColor: Colors.verde,
    borderRadius: 20,
    padding: 24,
    alignItems: 'center',
    gap: 4,
  },
  avatar: {
    width: 72, height: 72,
    borderRadius: 36,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
  },
  avatarTexto: { fontSize: 32, fontWeight: 'bold', color: Colors.blanco },
  nombre: { fontSize: 20, fontWeight: '700', color: Colors.blanco },
  correo: { fontSize: 13, color: 'rgba(255,255,255,0.85)' },
  cedula: { fontSize: 12, color: 'rgba(255,255,255,0.7)' },
  seccion: {
    backgroundColor: Colors.blanco,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    gap: 10,
  },
  seccionTitulo: { fontSize: 15, fontWeight: '700', color: Colors.negro, marginBottom: 4 },
  infoFila: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  infoLabel: { fontSize: 14, color: Colors.grisMedio },
  infoValor: { fontSize: 14, color: Colors.negro },
  estadoBadge: {
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 8,
    alignSelf: 'flex-start',
  },
  estadoTexto: { fontSize: 14, fontWeight: '600' },
  postularContainer: { gap: 10, marginTop: 4 },
  postularDesc: { fontSize: 13, color: Colors.grisOscuro, lineHeight: 18 },
  input: {
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 14,
    color: Colors.negro,
  },
  btnPostular: {
    backgroundColor: Colors.naranja,
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
  },
  btnDeshabilitado: { opacity: 0.6 },
  btnPostularTexto: { color: Colors.blanco, fontSize: 15, fontWeight: '600' },
  pendienteTexto: { fontSize: 13, color: '#92400e', lineHeight: 18 },
  aprobadoTexto: { fontSize: 13, color: Colors.verde, lineHeight: 18 },
  btnRepartidor: {
    backgroundColor: Colors.naranja,
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
  },
  btnRepartidorTexto: { color: Colors.blanco, fontSize: 15, fontWeight: '600' },
  btnLogout: {
    borderWidth: 2,
    borderColor: '#ef4444',
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  btnLogoutTexto: { color: '#ef4444', fontSize: 15, fontWeight: '600' },
  backendDebug: {
    fontSize: 11,
    color: Colors.grisMedio,
    textAlign: 'center',
    marginTop: 4,
  },
});