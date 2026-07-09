import React, { useState, useCallback } from 'react';
import {
  View, Text, StyleSheet, ScrollView,
  ActivityIndicator, RefreshControl, TouchableOpacity, Alert,
  Dimensions,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import api from '../../services/api';
import { Pedido } from '../../types';
import { Colors } from '../../constants/colors';
import MapaCampusPreview from '../../components/MapaCampusPreview';

const MAPA_WIDTH = Dimensions.get('window').width - 64; // ancho de pantalla menos padding de la seccion (16*2) y card (16*2)

export default function ViajeActualScreen() {
  const [pedido, setPedido] = useState<Pedido | null>(null);
  const [cargando, setCargando] = useState(true);
  const [completando, setCompletando] = useState(false);

  const cargar = async () => {
    setCargando(true);
    try {
      const r = await api.get<Pedido | null>('/repartidor/viaje-actual');
      setPedido(r.data);
    } catch (e) {
      console.error(e);
    } finally {
      setCargando(false);
    }
  };

  useFocusEffect(useCallback(() => {
    cargar();
    const interval = setInterval(cargar, 15000);
    return () => clearInterval(interval);
  }, []));

  if (cargando && !pedido) {
    return <ActivityIndicator size="large" color={Colors.verde} style={styles.loader} />;
  }

  if (!pedido) {
    return (
      <View style={styles.vacio}>
        <Text style={styles.vacioIcono}>🛵</Text>
        <Text style={styles.vacioTitulo}>Sin viaje activo</Text>
        <Text style={styles.vacioSub}>
          Cuando aceptes un viaje aparecerá aquí con todos los detalles del cliente
        </Text>
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={cargando} onRefresh={cargar} tintColor={Colors.verde} />}
    >
      {/* Header del viaje */}
      <View style={styles.headerCard}>
        <View style={styles.headerRow}>
          <Text style={styles.pedidoLabel}>Viaje en curso</Text>
          <Text style={styles.pedidoId}>Pedido #{pedido.id}</Text>
        </View>
        <Text style={styles.total}>${Number(pedido.total).toFixed(2)}</Text>
        <View style={styles.incentivoBadge}>
          <Text style={styles.incentivoTexto}>💰 Ganarás $0.25 al completar esta entrega</Text>
        </View>
      </View>

      {/* Datos del cliente */}
      <View style={styles.seccion}>
        <Text style={styles.seccionTitulo}>👤 Cliente</Text>
        <Text style={styles.datoValor}>{pedido.cliente?.nombre_completo}</Text>
        <Text style={styles.datoSub}>{pedido.cliente?.correo}</Text>
        <Text style={styles.datoSub}>C.I. {pedido.cliente?.cedula}</Text>
      </View>

      {/* Ubicación de entrega */}
      {pedido.punto_encuentro && (
        <View style={styles.seccion}>
          <Text style={styles.seccionTitulo}>📍 Punto de encuentro</Text>
          <Text style={styles.datoValor}>{pedido.punto_encuentro}</Text>
          {pedido.pin_x !== null && pedido.pin_y !== null && (
            <View style={styles.mapaContainer}>
              <MapaCampusPreview pinX={pedido.pin_x} pinY={pedido.pin_y} width={MAPA_WIDTH} />
            </View>
          )}
        </View>
      )}

      {/* Productos */}
      <View style={styles.seccion}>
        <Text style={styles.seccionTitulo}>🛍️ Productos a entregar</Text>
        {pedido.detalles?.map(d => (
          <View key={d.id} style={styles.productoFila}>
            <Text style={styles.productoNombre}>
              {d.producto?.nombre}
            </Text>
            <Text style={styles.productoCantidad}>× {d.cantidad}</Text>
          </View>
        ))}
        <View style={styles.totalFila}>
          <Text style={styles.totalLabel}>Total del pedido</Text>
          <Text style={styles.totalValor}>${Number(pedido.total).toFixed(2)}</Text>
        </View>
      </View>

      {/* Instrucciones */}
      <View style={styles.instruccionesCard}>
        <Text style={styles.instruccionesTitulo}>¿Cómo completar la entrega?</Text>
        <Text style={styles.instruccionItem}>1. Ve al punto de encuentro indicado</Text>
        <Text style={styles.instruccionItem}>2. Entrega el pedido al cliente</Text>
        <Text style={styles.instruccionItem}>3. Ve al tab "Escanear" y escanea el QR del cliente</Text>
        <Text style={styles.instruccionItem}>4. Se acreditarán $0.25 a tu billetera automáticamente</Text>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  loader: { flex: 1, marginTop: 40 },
  container: { flex: 1, backgroundColor: Colors.fondo },
  content: { padding: 16, gap: 12, paddingBottom: 40 },
  vacio: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
    gap: 12,
  },
  vacioIcono: { fontSize: 64 },
  vacioTitulo: { fontSize: 20, fontWeight: '700', color: Colors.negro },
  vacioSub: { fontSize: 14, color: Colors.grisMedio, textAlign: 'center', lineHeight: 20 },
  headerCard: {
    backgroundColor: Colors.naranja,
    borderRadius: 16,
    padding: 20,
    gap: 8,
  },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  pedidoLabel: { fontSize: 13, color: 'rgba(255,255,255,0.8)' },
  pedidoId: { fontSize: 13, color: 'rgba(255,255,255,0.8)', fontWeight: '600' },
  total: { fontSize: 36, fontWeight: 'bold', color: Colors.blanco },
  incentivoBadge: {
    backgroundColor: 'rgba(255,255,255,0.2)',
    borderRadius: 8,
    padding: 8,
  },
  incentivoTexto: { fontSize: 12, color: Colors.blanco, textAlign: 'center' },
  seccion: {
    backgroundColor: Colors.blanco,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    gap: 4,
  },
  seccionTitulo: { fontSize: 13, fontWeight: '700', color: Colors.grisMedio, marginBottom: 4 },
  datoValor: { fontSize: 16, fontWeight: '600', color: Colors.negro },
  mapaContainer: { marginTop: 8 },
  datoSub: { fontSize: 13, color: Colors.grisMedio },
  productoFila: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 4,
    borderBottomWidth: 1,
    borderBottomColor: Colors.grisClaro,
  },
  productoNombre: { fontSize: 14, color: Colors.negro },
  productoCantidad: { fontSize: 14, fontWeight: '600', color: Colors.verde },
  totalFila: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 8,
    paddingTop: 8,
  },
  totalLabel: { fontSize: 14, fontWeight: '600', color: Colors.grisOscuro },
  totalValor: { fontSize: 16, fontWeight: 'bold', color: Colors.verde },
  instruccionesCard: {
    backgroundColor: '#f0faf0',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: '#86efac',
    gap: 6,
  },
  instruccionesTitulo: { fontSize: 14, fontWeight: '700', color: Colors.verde, marginBottom: 4 },
  instruccionItem: { fontSize: 13, color: Colors.grisOscuro, lineHeight: 18 },
});