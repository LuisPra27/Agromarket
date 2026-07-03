import React, { useEffect, useState, useCallback } from 'react';
import {
  View, Text, FlatList, StyleSheet,
  ActivityIndicator, TouchableOpacity, RefreshControl,
} from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import api from '../../services/api';
import { Pedido } from '../../types';
import { Colors } from '../../constants/colors';

const ESTADO_CONFIG: Record<string, { label: string; color: string }> = {
  pendiente_validacion: { label: '⏳ Pendiente de validación', color: '#f59e0b' },
  rechazado:           { label: '❌ Rechazado', color: '#ef4444' },
  preparando:          { label: '👨‍🍳 Preparando', color: '#3b82f6' },
  listo_para_delivery: { label: '📦 Listo para delivery', color: '#8b5cf6' },
  en_camino:           { label: '🛵 En camino', color: '#f97316' },
  entregado:           { label: '✅ Entregado', color: '#16a34a' },
};

export default function MisPedidosScreen() {
  const [pedidos, setPedidos] = useState<Pedido[]>([]);
  const [cargando, setCargando] = useState(true);
  const navigation = useNavigation<any>();

  const cargar = async () => {
    setCargando(true);
    try {
      const r = await api.get<Pedido[]>('/pedidos');
      setPedidos(r.data);
    } catch (e) {
      console.error(e);
    } finally {
      setCargando(false);
    }
  };

  useFocusEffect(useCallback(() => { cargar(); }, []));

  const renderPedido = ({ item }: { item: Pedido }) => {
    const estado = ESTADO_CONFIG[item.estado] ?? { label: item.estado, color: Colors.grisMedio };
    return (
      <TouchableOpacity
        style={styles.card}
        onPress={() => navigation.navigate('SeguimientoPedido', { pedidoId: item.id })}
      >
        <View style={styles.cardHeader}>
          <Text style={styles.pedidoId}>Pedido #{item.id}</Text>
          <Text style={styles.fecha}>
            {new Date(item.created_at!).toLocaleDateString('es-EC')}
          </Text>
        </View>

        <View style={[styles.estadoBadge, { backgroundColor: estado.color + '20' }]}>
          <Text style={[styles.estadoTexto, { color: estado.color }]}>{estado.label}</Text>
        </View>

        <View style={styles.cardFooter}>
          <Text style={styles.metodo}>
            {item.metodo_entrega === 'retiro' ? '🏪 Retiro' : '🛵 Delivery'}
          </Text>
          <Text style={styles.total}>${Number(item.total).toFixed(2)}</Text>
        </View>
      </TouchableOpacity>
    );
  };

  if (cargando) return <ActivityIndicator size="large" color={Colors.verde} style={styles.loader} />;

  return (
    <FlatList
      data={pedidos}
      keyExtractor={item => item.id.toString()}
      renderItem={renderPedido}
      contentContainerStyle={styles.lista}
      refreshControl={<RefreshControl refreshing={cargando} onRefresh={cargar} tintColor={Colors.verde} />}
      ListEmptyComponent={
        <View style={styles.vacio}>
          <Text style={styles.vacioTexto}>📋</Text>
          <Text style={styles.vacioTitulo}>Sin pedidos aún</Text>
          <Text style={styles.vacioSub}>Tus pedidos aparecerán aquí</Text>
        </View>
      }
    />
  );
}

const styles = StyleSheet.create({
  loader: { flex: 1, marginTop: 40 },
  lista: { padding: 16, gap: 12 },
  card: {
    backgroundColor: Colors.blanco,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    gap: 10,
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between' },
  pedidoId: { fontSize: 15, fontWeight: '700', color: Colors.negro },
  fecha: { fontSize: 12, color: Colors.grisMedio },
  estadoBadge: { borderRadius: 8, paddingHorizontal: 10, paddingVertical: 6, alignSelf: 'flex-start' },
  estadoTexto: { fontSize: 13, fontWeight: '600' },
  cardFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  metodo: { fontSize: 13, color: Colors.grisOscuro },
  total: { fontSize: 16, fontWeight: 'bold', color: Colors.verde },
  vacio: { flex: 1, alignItems: 'center', marginTop: 80, gap: 8 },
  vacioTexto: { fontSize: 64 },
  vacioTitulo: { fontSize: 18, fontWeight: '600', color: Colors.negro },
  vacioSub: { fontSize: 14, color: Colors.grisMedio },
});