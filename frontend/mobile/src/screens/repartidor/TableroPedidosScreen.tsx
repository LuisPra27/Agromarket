import React, { useState, useCallback } from 'react';
import {
  View, Text, FlatList, StyleSheet,
  TouchableOpacity, Alert, ActivityIndicator, RefreshControl,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import api from '../../services/api';
import { Pedido } from '../../types';
import { Colors } from '../../constants/colors';
import { subscribeToPedidosRepartidores } from '../../services/realtime';

export default function TableroPedidosScreen() {
  const [pedidos, setPedidos] = useState<Pedido[]>([]);
  const [viajeActivo, setViajeActivo] = useState<Pedido | null>(null);
  const [cargando, setCargando] = useState(true);
  const [aceptando, setAceptando] = useState<number | null>(null);

  const cargar = useCallback(async () => {
    setCargando(true);
    try {
      // Primero verificamos si ya tiene un viaje en curso: si es así, no
      // tiene sentido mostrarle la lista de disponibles (el backend igual
      // rechazaría cualquier "Aceptar", pero es mejor prevenir el tap).
      const viaje = await api.get<Pedido | null>('/repartidor/viaje-actual');

      // Igual que en ViajeActualScreen: un pedido "en_camino" real siempre
      // trae cliente y un total válido. Si llega incompleto, lo tratamos
      // como si no hubiera viaje activo en vez de bloquear la pantalla.
      const viajeValido = viaje.data
        && viaje.data.cliente
        && !Number.isNaN(Number(viaje.data.total))
        ? viaje.data
        : null;

      setViajeActivo(viajeValido);

      if (viajeValido) {
        setPedidos([]);
      } else {
        const r = await api.get<Pedido[]>('/repartidor/disponibles');
        setPedidos(r.data);
      }
    } catch (e) {
      console.error(e);
      setViajeActivo(null);
    } finally {
      setCargando(false);
    }
  }, []);

  useFocusEffect(useCallback(() => {
    let pantallaActiva = true;

    cargar();

    const limpiarRealtime = subscribeToPedidosRepartidores(() => {
      if (pantallaActiva) {
        cargar();
      }
    });

    const interval = setInterval(cargar, 10000);
    return () => {
      pantallaActiva = false;
      clearInterval(interval);
      limpiarRealtime();
    };
  }, [cargar]));

  const aceptarViaje = async (pedido: Pedido) => {
    Alert.alert(
      'Aceptar viaje',
      `¿Confirmas que vas a entregar el pedido #${pedido.id} a ${pedido.cliente?.nombre_completo}?\n\nDestino: ${pedido.punto_encuentro ?? 'Retiro en local'}`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Aceptar viaje',
          onPress: async () => {
            setAceptando(pedido.id);
            try {
              await api.post(`/repartidor/${pedido.id}/accept`);
              Alert.alert('¡Viaje aceptado!', 'Ve a buscar el pedido y entrégalo al cliente.');
              cargar();
            } catch (error: any) {
              Alert.alert('Error', error.response?.data?.message ?? 'No se pudo aceptar el viaje.');
            } finally {
              setAceptando(null);
            }
          },
        },
      ]
    );
  };

  const renderPedido = ({ item }: { item: Pedido }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <Text style={styles.pedidoId}>Pedido #{item.id}</Text>
        <Text style={styles.total}>${Number(item.total).toFixed(2)}</Text>
      </View>

      <Text style={styles.cliente}>👤 {item.cliente?.nombre_completo}</Text>

      {item.punto_encuentro && (
        <Text style={styles.destino}>📍 {item.punto_encuentro}</Text>
      )}

      <View style={styles.productos}>
        {item.detalles?.map(d => (
          <Text key={d.id} style={styles.productoItem}>
            • {d.producto?.nombre} × {d.cantidad}
          </Text>
        ))}
      </View>

      <View style={styles.incentivo}>
        <Text style={styles.incentivoTexto}>💰 Ganarás $0.25 por esta entrega</Text>
      </View>

      <TouchableOpacity
        style={[styles.btnAceptar, aceptando === item.id && styles.btnDeshabilitado]}
        onPress={() => aceptarViaje(item)}
        disabled={aceptando === item.id}
      >
        {aceptando === item.id ? (
          <ActivityIndicator color={Colors.blanco} />
        ) : (
          <Text style={styles.btnAceptarTexto}>Aceptar viaje</Text>
        )}
      </TouchableOpacity>
    </View>
  );

  return (
    <View style={styles.container}>
      {cargando && pedidos.length === 0 && !viajeActivo ? (
        <ActivityIndicator size="large" color={Colors.verde} style={styles.loader} />
      ) : viajeActivo ? (
        <View style={styles.vacio}>
          <Text style={styles.vacioTexto}>🚧</Text>
          <Text style={styles.vacioTitulo}>Ya tienes un viaje en curso</Text>
          <Text style={styles.vacioSub}>
            Completa la entrega del pedido #{viajeActivo.id} en la pestaña "En curso" antes de aceptar otro viaje.
          </Text>
        </View>
      ) : (
        <FlatList
          data={pedidos}
          keyExtractor={item => item.id.toString()}
          renderItem={renderPedido}
          contentContainerStyle={styles.lista}
          refreshControl={<RefreshControl refreshing={cargando} onRefresh={cargar} tintColor={Colors.verde} />}
          ListHeaderComponent={
            <Text style={styles.headerTexto}>
              {pedidos.length > 0
                ? `${pedidos.length} viaje${pedidos.length > 1 ? 's' : ''} disponible${pedidos.length > 1 ? 's' : ''}`
                : 'Sin viajes disponibles ahora'}
            </Text>
          }
          ListEmptyComponent={
            <View style={styles.vacio}>
              <Text style={styles.vacioTexto}>🛵</Text>
              <Text style={styles.vacioTitulo}>Sin viajes por ahora</Text>
              <Text style={styles.vacioSub}>Los pedidos nuevos aparecerán aquí automáticamente</Text>
            </View>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.fondo },
  loader: { flex: 1, marginTop: 40 },
  lista: { padding: 16, gap: 12 },
  headerTexto: { fontSize: 13, color: Colors.grisMedio, marginBottom: 4, textAlign: 'center' },
  card: {
    backgroundColor: Colors.blanco,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    gap: 8,
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  pedidoId: { fontSize: 15, fontWeight: '700', color: Colors.negro },
  total: { fontSize: 16, fontWeight: 'bold', color: Colors.verde },
  cliente: { fontSize: 14, color: Colors.grisOscuro },
  destino: { fontSize: 13, color: Colors.naranja, fontWeight: '500' },
  productos: { gap: 2 },
  productoItem: { fontSize: 13, color: Colors.grisMedio },
  incentivo: {
    backgroundColor: '#fef9c3',
    borderRadius: 8,
    padding: 8,
  },
  incentivoTexto: { fontSize: 12, color: '#854d0e', textAlign: 'center' },
  btnAceptar: {
    backgroundColor: Colors.verde,
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 4,
  },
  btnDeshabilitado: { opacity: 0.6 },
  btnAceptarTexto: { color: Colors.blanco, fontSize: 15, fontWeight: '600' },
  vacio: { alignItems: 'center', marginTop: 60, gap: 8 },
  vacioTexto: { fontSize: 64 },
  vacioTitulo: { fontSize: 18, fontWeight: '600', color: Colors.negro },
  vacioSub: { fontSize: 13, color: Colors.grisMedio, textAlign: 'center' },
});