import React from 'react';
import {
  View, Text, FlatList, TouchableOpacity,
  Image, StyleSheet, Alert,
} from 'react-native';
import { useCarrito } from '../../store/CarritoContext';
import { API_URL } from '../../services/api';
import { ItemCarrito } from '../../types';
import { useNavigation } from '@react-navigation/native';

export default function CarritoScreen() {
  const { items, quitar, cambiarCantidad, total, limpiar } = useCarrito();
  const navigation = useNavigation<any>();

  const confirmarLimpiar = () => {
    Alert.alert(
      'Vaciar carrito',
      '¿Estás seguro de que quieres eliminar todos los productos?',
      [
        { text: 'Cancelar', style: 'cancel' },
        { text: 'Vaciar', style: 'destructive', onPress: limpiar },
      ]
    );
  };

  const renderItem = ({ item }: { item: ItemCarrito }) => (
    <View style={styles.card}>
      {item.producto.imagen_url ? (
        <Image
          source={{ uri: `${API_URL}/archivos/${item.producto.imagen_url}` }}
          style={styles.imagen}
          resizeMode="cover"
        />
      ) : (
        <View style={[styles.imagen, styles.sinImagen]}>
          <Text style={styles.sinImagenTexto}>Sin foto</Text>
        </View>
      )}
      <View style={styles.info}>
        <Text style={styles.nombre}>{item.producto.nombre}</Text>
        <Text style={styles.precioUnit}>
          ${Number(item.producto.precio).toFixed(2)} c/u
        </Text>
        <View style={styles.controles}>
          <TouchableOpacity
            style={styles.btn}
            onPress={() => cambiarCantidad(item.producto.id, item.cantidad - 1)}
          >
            <Text style={styles.btnTexto}>−</Text>
          </TouchableOpacity>
          <Text style={styles.cantidad}>{item.cantidad}</Text>
          <TouchableOpacity
            style={styles.btn}
            onPress={() => cambiarCantidad(item.producto.id, item.cantidad + 1)}
          >
            <Text style={styles.btnTexto}>+</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.btnEliminar}
            onPress={() => quitar(item.producto.id)}
          >
            <Text style={styles.btnEliminarTexto}>✕</Text>
          </TouchableOpacity>
        </View>
      </View>
      <Text style={styles.subtotal}>
        ${(Number(item.producto.precio) * item.cantidad).toFixed(2)}
      </Text>
    </View>
  );

  if (items.length === 0) {
    return (
      <View style={styles.vacio}>
        <Text style={styles.vacioTexto}>🛒</Text>
        <Text style={styles.vacioTitulo}>Tu carrito está vacío</Text>
        <Text style={styles.vacioSub}>Agrega productos desde el catálogo</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={items}
        keyExtractor={item => item.producto.id.toString()}
        renderItem={renderItem}
        contentContainerStyle={styles.lista}
      />

      {/* Footer con total y botones */}
      <View style={styles.footer}>
        <View style={styles.totalRow}>
          <Text style={styles.totalLabel}>Total:</Text>
          <Text style={styles.totalMonto}>${total.toFixed(2)}</Text>
        </View>

        <TouchableOpacity
          style={styles.btnPagar}
          onPress={() => navigation.navigate('Checkout')}
        >
          <Text style={styles.btnPagarTexto}>Ir a pagar</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.btnSeguir}
          onPress={() => navigation.navigate('Catálogo')}
        >
          <Text style={styles.btnSeguirTexto}>Seguir comprando</Text>
        </TouchableOpacity>

        <TouchableOpacity onPress={confirmarLimpiar}>
          <Text style={styles.btnVaciar}>Vaciar carrito</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f9fafb' },
  lista: { padding: 16, gap: 12, paddingBottom: 0 },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    flexDirection: 'row',
    borderWidth: 1,
    borderColor: '#e5e7eb',
    overflow: 'hidden',
    alignItems: 'center',
  },
  imagen: { width: 80, height: 80 },
  sinImagen: { backgroundColor: '#f3f4f6', justifyContent: 'center', alignItems: 'center' },
  sinImagenTexto: { fontSize: 10, color: '#9ca3af' },
  info: { flex: 1, padding: 10 },
  nombre: { fontSize: 14, fontWeight: '600', color: '#111827' },
  precioUnit: { fontSize: 12, color: '#6b7280', marginTop: 2 },
  controles: { flexDirection: 'row', alignItems: 'center', marginTop: 8, gap: 8 },
  btn: {
    backgroundColor: '#16a34a',
    width: 28, height: 28,
    borderRadius: 14,
    justifyContent: 'center',
    alignItems: 'center',
  },
  btnTexto: { color: '#ffffff', fontSize: 16, fontWeight: 'bold' },
  cantidad: { fontSize: 15, fontWeight: '600', minWidth: 24, textAlign: 'center' },
  btnEliminar: {
    marginLeft: 8,
    backgroundColor: '#fee2e2',
    width: 28, height: 28,
    borderRadius: 14,
    justifyContent: 'center',
    alignItems: 'center',
  },
  btnEliminarTexto: { color: '#ef4444', fontSize: 12, fontWeight: 'bold' },
  subtotal: { fontSize: 15, fontWeight: 'bold', color: '#16a34a', paddingRight: 12 },
  footer: {
    backgroundColor: '#ffffff',
    padding: 20,
    borderTopWidth: 1,
    borderTopColor: '#e5e7eb',
    gap: 12,
  },
  totalRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  totalLabel: { fontSize: 16, color: '#374151' },
  totalMonto: { fontSize: 22, fontWeight: 'bold', color: '#111827' },
  btnPagar: {
    backgroundColor: '#16a34a',
    borderRadius: 14,
    paddingVertical: 16,
    alignItems: 'center',
  },
  btnPagarTexto: { color: '#ffffff', fontSize: 16, fontWeight: '600' },
  btnSeguir: {
    backgroundColor: '#f3f4f6',
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  btnSeguirTexto: { color: '#374151', fontSize: 15, fontWeight: '500' },
  btnVaciar: { color: '#ef4444', textAlign: 'center', fontSize: 13 },
  vacio: { flex: 1, justifyContent: 'center', alignItems: 'center', gap: 8 },
  vacioTexto: { fontSize: 64 },
  vacioTitulo: { fontSize: 18, fontWeight: '600', color: '#111827' },
  vacioSub: { fontSize: 14, color: '#6b7280' },
});