import React, { useCallback, useState } from 'react';
import {
  View, Text, StyleSheet, ActivityIndicator,
  ScrollView, RefreshControl,
} from 'react-native';
import QRCode from 'react-native-qrcode-svg';
import { useFocusEffect, useRoute } from '@react-navigation/native';
import api from '../../services/api';
import { Pedido } from '../../types';
import { Colors } from '../../constants/colors';

const ESTADOS = [
  { key: 'pendiente_validacion', label: 'Pendiente de validación' },
  { key: 'preparando', label: 'Preparando tu pedido' },
  { key: 'listo_para_delivery', label: 'Listo para delivery' },
  { key: 'en_camino', label: 'En camino' },
  { key: 'entregado', label: 'Entregado' },
];

export default function SeguimientoPedidoScreen() {
  const route = useRoute<any>();
  const { pedidoId } = route.params;
  const [pedido, setPedido] = useState<Pedido | null>(null);
  const [cargando, setCargando] = useState(true);

  const cargar = async () => {
    try {
      const r = await api.get<Pedido>(`/pedidos/${pedidoId}`);
      setPedido(r.data);
    } catch (e) {
      console.error(e);
    } finally {
      setCargando(false);
    }
  };

  useFocusEffect(useCallback(() => {
    cargar();
    const interval = setInterval(cargar, 10000); // polling cada 10s
    return () => clearInterval(interval);
  }, [pedidoId]));

  if (cargando || !pedido) {
    return <ActivityIndicator size="large" color={Colors.verde} style={styles.loader} />;
  }

  const estadoActualIndex = ESTADOS.findIndex(e => e.key === pedido.estado);
  const mostrarQR = pedido.codigo_qr_hash && ['preparando', 'listo_para_delivery', 'en_camino'].includes(pedido.estado);

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={cargando} onRefresh={cargar} tintColor={Colors.verde} />}
    >
      {/* Header */}
      <View style={styles.headerCard}>
        <Text style={styles.pedidoId}>Pedido #{pedido.id}</Text>
        <Text style={styles.total}>${Number(pedido.total).toFixed(2)}</Text>
        <Text style={styles.metodo}>
          {pedido.metodo_entrega === 'retiro' ? '🏪 Retiro en local' : '🛵 Delivery'}
        </Text>
      </View>

      {/* QR Code */}
      {mostrarQR && (
        <View style={styles.qrCard}>
          <Text style={styles.qrTitulo}>Tu código QR</Text>
          <Text style={styles.qrSubtitulo}>
            Muéstraselo al repartidor para confirmar la entrega
          </Text>
          <View style={styles.qrContainer}>
            <QRCode
              value={pedido.codigo_qr_hash!}
              size={220}
              color={Colors.verde}
              backgroundColor={Colors.blanco}
            />
          </View>
          <Text style={styles.qrCodigo}>{pedido.codigo_qr_hash}</Text>
        </View>
      )}

      {/* Pedido rechazado */}
      {pedido.estado === 'rechazado' && (
        <View style={styles.rechazadoCard}>
          <Text style={styles.rechazadoTexto}>❌ Pedido rechazado</Text>
          <Text style={styles.rechazadoSub}>
            El comprobante no fue validado. Contacta al administrador.
          </Text>
        </View>
      )}

      {/* Timeline de estados */}
      <View style={styles.timelineCard}>
        <Text style={styles.sectionTitulo}>Estado del pedido</Text>
        {ESTADOS.map((estado, index) => {
          const completado = index <= estadoActualIndex;
          const actual = index === estadoActualIndex;
          return (
            <View key={estado.key} style={styles.timelineItem}>
              <View style={styles.timelineLeft}>
                <View style={[
                  styles.timelineDot,
                  completado && styles.timelineDotActivo,
                  actual && styles.timelineDotActual,
                ]} />
                {index < ESTADOS.length - 1 && (
                  <View style={[styles.timelineLine, completado && styles.timelineLineActiva]} />
                )}
              </View>
              <Text style={[
                styles.timelineLabel,
                completado && styles.timelineLabelActivo,
                actual && styles.timelineLabelActual,
              ]}>
                {estado.label}
              </Text>
            </View>
          );
        })}
      </View>

      {/* Detalle de productos */}
      <View style={styles.detalleCard}>
        <Text style={styles.sectionTitulo}>Productos</Text>
        {pedido.detalles?.map(detalle => (
          <View key={detalle.id} style={styles.detalleRow}>
            <Text style={styles.detalleNombre}>
              {detalle.producto?.nombre} × {detalle.cantidad}
            </Text>
            <Text style={styles.detallePrecio}>
              ${Number(detalle.subtotal).toFixed(2)}
            </Text>
          </View>
        ))}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  loader: { flex: 1, marginTop: 40 },
  container: { flex: 1, backgroundColor: Colors.fondo },
  content: { padding: 16, gap: 12, paddingBottom: 40 },
  headerCard: {
    backgroundColor: Colors.verde,
    borderRadius: 16,
    padding: 20,
    alignItems: 'center',
    gap: 4,
  },
  pedidoId: { fontSize: 14, color: 'rgba(255,255,255,0.8)' },
  total: { fontSize: 32, fontWeight: 'bold', color: Colors.blanco },
  metodo: { fontSize: 14, color: 'rgba(255,255,255,0.9)' },
  qrCard: {
    backgroundColor: Colors.blanco,
    borderRadius: 16,
    padding: 20,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    gap: 8,
  },
  qrTitulo: { fontSize: 18, fontWeight: '700', color: Colors.negro },
  qrSubtitulo: { fontSize: 13, color: Colors.grisMedio, textAlign: 'center' },
  qrContainer: {
    padding: 16,
    backgroundColor: Colors.blanco,
    borderRadius: 12,
    marginVertical: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 8,
    elevation: 4,
  },
  qrCodigo: { fontSize: 10, color: Colors.grisMedio, fontFamily: 'monospace' },
  rechazadoCard: {
    backgroundColor: '#fee2e2',
    borderRadius: 16,
    padding: 16,
    alignItems: 'center',
    gap: 4,
  },
  rechazadoTexto: { fontSize: 16, fontWeight: '700', color: '#ef4444' },
  rechazadoSub: { fontSize: 13, color: '#b91c1c', textAlign: 'center' },
  timelineCard: {
    backgroundColor: Colors.blanco,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
  },
  sectionTitulo: { fontSize: 15, fontWeight: '700', color: Colors.negro, marginBottom: 12 },
  timelineItem: { flexDirection: 'row', alignItems: 'flex-start', gap: 12, minHeight: 40 },
  timelineLeft: { alignItems: 'center', width: 20 },
  timelineDot: {
    width: 16, height: 16,
    borderRadius: 8,
    backgroundColor: Colors.grisClaro,
    borderWidth: 2,
    borderColor: Colors.grisMedio,
  },
  timelineDotActivo: { backgroundColor: Colors.verde, borderColor: Colors.verde },
  timelineDotActual: {
    width: 20, height: 20,
    borderRadius: 10,
    backgroundColor: Colors.verde,
    borderColor: Colors.verdeClaro,
    borderWidth: 3,
  },
  timelineLine: { width: 2, flex: 1, backgroundColor: Colors.grisClaro, minHeight: 20 },
  timelineLineActiva: { backgroundColor: Colors.verde },
  timelineLabel: { fontSize: 13, color: Colors.grisMedio, paddingTop: 1 },
  timelineLabelActivo: { color: Colors.grisOscuro },
  timelineLabelActual: { color: Colors.verde, fontWeight: '700' },
  detalleCard: {
    backgroundColor: Colors.blanco,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    gap: 8,
  },
  detalleRow: { flexDirection: 'row', justifyContent: 'space-between' },
  detalleNombre: { fontSize: 14, color: Colors.grisOscuro },
  detallePrecio: { fontSize: 14, fontWeight: '600', color: Colors.negro },
});