import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, Alert, TouchableOpacity } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import AsyncStorage from '@react-native-async-storage/async-storage';
import NetInfo from '@react-native-community/netinfo';
import api from '../../services/api';
import { Colors } from '../../constants/colors';

const PENDING_SCANS_KEY = 'pending_qr_scans';

export default function EscanerQRScreen() {
  const [permission, requestPermission] = useCameraPermissions();
  const [escaneado, setEscaneado] = useState(false);
  const [procesando, setProcesando] = useState(false);

  useEffect(() => {
    // Al abrir la pantalla, intentar enviar escaneos pendientes
    reintentarPendientes();
  }, []);

  const reintentarPendientes = async () => {
    const netInfo = await NetInfo.fetch();
    if (!netInfo.isConnected) return;

    try {
      const pendientesRaw = await AsyncStorage.getItem(PENDING_SCANS_KEY);
      if (!pendientesRaw) return;

      const pendientes: Array<{ pedidoId: number; codigo: string }> = JSON.parse(pendientesRaw);
      if (pendientes.length === 0) return;

      const exitosos: number[] = [];
      for (const scan of pendientes) {
        try {
          await api.post(`/repartidor/${scan.pedidoId}/complete`, { codigo_qr: scan.codigo });
          exitosos.push(scan.pedidoId);
        } catch (e) {
          // Dejarlo pendiente para el próximo intento
        }
      }

      if (exitosos.length > 0) {
        const restantes = pendientes.filter(p => !exitosos.includes(p.pedidoId));
        await AsyncStorage.setItem(PENDING_SCANS_KEY, JSON.stringify(restantes));
        Alert.alert('Sincronización', `Se sincronizaron ${exitosos.length} entrega(s) pendiente(s).`);
      }
    } catch (e) {
      console.error('Error reintentando pendientes:', e);
    }
  };

  const handleScan = async (pedidoId: number, codigoQr: string) => {
    if (escaneado || procesando) return;
    setEscaneado(true);
    setProcesando(true);

    const netInfo = await NetInfo.fetch();

    if (!netInfo.isConnected) {
      // Guardar localmente para reintento posterior
      try {
        const pendientesRaw = await AsyncStorage.getItem(PENDING_SCANS_KEY);
        const pendientes = pendientesRaw ? JSON.parse(pendientesRaw) : [];
        pendientes.push({ pedidoId, codigo: codigoQr });
        await AsyncStorage.setItem(PENDING_SCANS_KEY, JSON.stringify(pendientes));

        Alert.alert(
          'Sin conexión',
          'El escaneo se guardó localmente. Se enviará automáticamente cuando recuperes la conexión.',
          [{ text: 'OK', onPress: () => setEscaneado(false) }]
        );
      } catch (e) {
        Alert.alert('Error', 'No se pudo guardar el escaneo localmente.');
        setEscaneado(false);
      }
      setProcesando(false);
      return;
    }

    try {
      await api.post(`/repartidor/${pedidoId}/complete`, { codigo_qr: codigoQr });
      Alert.alert(
        '✅ Entrega confirmada',
        'Se acreditaron $0.25 a tu billetera.',
        [{ text: 'OK', onPress: () => setEscaneado(false) }]
      );
    } catch (error: any) {
      Alert.alert(
        'Error',
        error.response?.data?.message ?? 'No se pudo confirmar la entrega.',
        [{ text: 'Reintentar', onPress: () => setEscaneado(false) }]
      );
    } finally {
      setProcesando(false);
    }
  };

  const onBarcodeScanned = ({ data }: { data: string }) => {
    // Formato esperado del QR: "agromarket:{pedidoId}:{codigoHash}"
    // O simplemente el hash directo si el QR solo contiene el hash
    // Por ahora asumimos que el QR tiene el formato: "{pedidoId}:{hash}"
    const partes = data.split(':');
    if (partes.length >= 2) {
      const pedidoId = parseInt(partes[0]);
      const codigo = partes.slice(1).join(':');
      if (!isNaN(pedidoId) && codigo) {
        handleScan(pedidoId, codigo);
        return;
      }
    }
    // Si el QR no tiene el formato esperado
    Alert.alert('QR inválido', 'Este código QR no corresponde a un pedido de Agromarket.');
    setTimeout(() => setEscaneado(false), 2000);
  };

  if (!permission) return <View />;

  if (!permission.granted) {
    return (
      <View style={styles.permiso}>
        <Text style={styles.permisoTexto}>Necesitamos acceso a la cámara para escanear el QR</Text>
        <TouchableOpacity style={styles.btnPermiso} onPress={requestPermission}>
          <Text style={styles.btnPermisoTexto}>Dar permiso</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <CameraView
        style={styles.camara}
        facing="back"
        onBarcodeScanned={escaneado ? undefined : onBarcodeScanned}
        barcodeScannerSettings={{ barcodeTypes: ['qr'] }}
      >
        <View style={styles.overlay}>
          <Text style={styles.instruccion}>
            {procesando ? 'Procesando...' : 'Apunta al código QR del cliente'}
          </Text>
          <View style={styles.marco} />
          {escaneado && !procesando && (
            <TouchableOpacity
              style={styles.btnReintentar}
              onPress={() => setEscaneado(false)}
            >
              <Text style={styles.btnReintentarTexto}>Escanear otro</Text>
            </TouchableOpacity>
          )}
        </View>
      </CameraView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  camara: { flex: 1 },
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 24,
  },
  instruccion: { color: Colors.blanco, fontSize: 16, fontWeight: '500', textAlign: 'center' },
  marco: {
    width: 250, height: 250,
    borderWidth: 3,
    borderColor: Colors.verde,
    borderRadius: 16,
    backgroundColor: 'transparent',
  },
  btnReintentar: {
    backgroundColor: Colors.verde,
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 12,
  },
  btnReintentarTexto: { color: Colors.blanco, fontSize: 15, fontWeight: '600' },
  permiso: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 32, gap: 16 },
  permisoTexto: { fontSize: 15, color: Colors.grisOscuro, textAlign: 'center' },
  btnPermiso: { backgroundColor: Colors.verde, borderRadius: 12, paddingVertical: 14, paddingHorizontal: 24 },
  btnPermisoTexto: { color: Colors.blanco, fontSize: 15, fontWeight: '600' },
});