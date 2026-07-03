import React, { useState } from 'react';
import {
  View, Text, FlatList, TextInput, TouchableOpacity,
  Image, StyleSheet, ActivityIndicator, RefreshControl,
} from 'react-native';
import { useProductos, useCategorias } from '../../hooks/useProductos';
import { useCarrito } from '../../store/CarritoContext';
import { API_URL } from '../../services/api';
import { Producto } from '../../types';

export default function CatalogoScreen() {
  const [categoriaId, setCategoriaId] = useState<number | undefined>();
  const [buscar, setBuscar] = useState('');
  const { productos, cargando, recargar } = useProductos(categoriaId, buscar);
  const { categorias } = useCategorias();
  const { agregar, items } = useCarrito();

  const enCarrito = (productoId: number) =>
    items.some(i => i.producto.id === productoId);

  const renderProducto = ({ item }: { item: Producto }) => (
    <TouchableOpacity style={styles.card} activeOpacity={0.8}>
      {item.imagen_url ? (
        <Image
          source={{ uri: `${API_URL}/archivos/${item.imagen_url}` }}
          style={styles.imagen}
          resizeMode="cover"
        />
      ) : (
        <View style={[styles.imagen, styles.sinImagen]}>
          <Text style={styles.sinImagenTexto}>Sin foto</Text>
        </View>
      )}
      <View style={styles.cardInfo}>
        <Text style={styles.nombre}>{item.nombre}</Text>
        <Text style={styles.categoria}>{item.categoria?.nombre}</Text>
        <View style={styles.cardFooter}>
          <Text style={styles.precio}>${Number(item.precio).toFixed(2)}</Text>
          <TouchableOpacity
            style={[styles.btnAgregar, enCarrito(item.id) && styles.btnAgregado]}
            onPress={() => agregar(item)}
          >
            <Text style={styles.btnAgregarTexto}>
              {enCarrito(item.id) ? '✓ Agregado' : 'Agregar'}
            </Text>
          </TouchableOpacity>
        </View>
      </View>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TextInput
          style={styles.buscador}
          placeholder="Buscar productos..."
          placeholderTextColor="#9ca3af"
          value={buscar}
          onChangeText={setBuscar}
        />
      </View>
      <View style={styles.categorias}>
        <TouchableOpacity
          style={[styles.chip, !categoriaId && styles.chipActivo]}
          onPress={() => setCategoriaId(undefined)}
        >
          <Text style={[styles.chipTexto, !categoriaId && styles.chipTextoActivo]}>
            Todos
          </Text>
        </TouchableOpacity>
        {categorias.map(cat => (
          <TouchableOpacity
            key={cat.id}
            style={[styles.chip, categoriaId === cat.id && styles.chipActivo]}
            onPress={() => setCategoriaId(cat.id)}
          >
            <Text style={[styles.chipTexto, categoriaId === cat.id && styles.chipTextoActivo]}>
              {cat.nombre}
            </Text>
          </TouchableOpacity>
        ))}
      </View>
      {cargando ? (
        <ActivityIndicator size="large" color="#16a34a" style={styles.loader} />
      ) : (
        <FlatList
          data={productos}
          keyExtractor={item => item.id.toString()}
          renderItem={renderProducto}
          contentContainerStyle={styles.lista}
          refreshControl={
            <RefreshControl refreshing={cargando} onRefresh={recargar} tintColor="#16a34a" />
          }
          ListEmptyComponent={
            <Text style={styles.vacio}>No hay productos disponibles.</Text>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f9fafb' },
  header: { padding: 16, paddingBottom: 8 },
  buscador: {
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#e5e7eb',
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 15,
    color: '#111827',
  },
  categorias: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    paddingBottom: 8,
    gap: 8,
    flexWrap: 'wrap',
  },
  chip: { paddingHorizontal: 14, paddingVertical: 6, borderRadius: 20, backgroundColor: '#e5e7eb' },
  chipActivo: { backgroundColor: '#16a34a' },
  chipTexto: { fontSize: 13, color: '#374151' },
  chipTextoActivo: { color: '#ffffff', fontWeight: '600' },
  lista: { padding: 16, gap: 12 },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    overflow: 'hidden',
    flexDirection: 'row',
    borderWidth: 1,
    borderColor: '#e5e7eb',
  },
  imagen: { width: 100, height: 100 },
  sinImagen: { backgroundColor: '#f3f4f6', justifyContent: 'center', alignItems: 'center' },
  sinImagenTexto: { fontSize: 12, color: '#9ca3af' },
  cardInfo: { flex: 1, padding: 12, justifyContent: 'space-between' },
  nombre: { fontSize: 15, fontWeight: '600', color: '#111827' },
  categoria: { fontSize: 12, color: '#6b7280', marginTop: 2 },
  cardFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 8 },
  precio: { fontSize: 16, fontWeight: 'bold', color: '#16a34a' },
  btnAgregar: { backgroundColor: '#16a34a', paddingHorizontal: 14, paddingVertical: 6, borderRadius: 20 },
  btnAgregado: { backgroundColor: '#6b7280' },
  btnAgregarTexto: { color: '#ffffff', fontSize: 13, fontWeight: '600' },
  loader: { flex: 1, marginTop: 40 },
  vacio: { textAlign: 'center', color: '#6b7280', marginTop: 40 },
});