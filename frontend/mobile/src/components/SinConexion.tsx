import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { Colors } from '../constants/colors';
import NetInfo from '@react-native-community/netinfo';

interface Props {
  onReintentar?: () => void;
}

export default function SinConexion({ onReintentar }: Props) {
  return (
    <View style={styles.container}>
      <Text style={styles.icono}>📡</Text>
      <Text style={styles.titulo}>Sin conexión</Text>
      <Text style={styles.subtitulo}>
        Verifica que estés conectado a Internet y vuelve a intentarlo.
      </Text>
      {onReintentar && (
        <TouchableOpacity
          style={styles.boton}
          onPress={onReintentar}
        >
          <Text style={styles.botonTexto}>Reintentar</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
    backgroundColor: Colors.fondo,
    gap: 12,
  },
  icono: { fontSize: 64 },
  titulo: { fontSize: 22, fontWeight: 'bold', color: Colors.negro },
  subtitulo: {
    fontSize: 14,
    color: Colors.grisMedio,
    textAlign: 'center',
    lineHeight: 20,
  },
  boton: {
    backgroundColor: Colors.verde,
    borderRadius: 12,
    paddingVertical: 14,
    paddingHorizontal: 32,
    marginTop: 8,
  },
  botonTexto: { color: Colors.blanco, fontSize: 15, fontWeight: '600' },
});