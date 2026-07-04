import React, { useState, useCallback } from 'react';
import { View, Text, StyleSheet, ScrollView, RefreshControl, ActivityIndicator } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import api from '../../services/api';
import { useAuth } from '../../store/AuthContext';
import { Colors } from '../../constants/colors';

interface Liquidacion {
  id: number;
  monto_pagado: number;
  created_at: string;
}

export default function BilleteraScreen() {
  const { usuario, actualizarUsuario } = useAuth();
  const [liquidaciones, setLiquidaciones] = useState<Liquidacion[]>([]);
  const [cargando, setCargando] = useState(true);

  const cargar = async () => {
    setCargando(true);
    try {
      const [meResp, liqResp] = await Promise.all([
        api.get('/auth/me'),
        api.get('/repartidor/mis-liquidaciones'),
      ]);
      actualizarUsuario(meResp.data);
      setLiquidaciones(liqResp.data);
    } catch (e) {
      console.error(e);
    } finally {
      setCargando(false);
    }
  };

  useFocusEffect(useCallback(() => { cargar(); }, []));

  if (cargando) return <ActivityIndicator size="large" color={Colors.verde} style={styles.loader} />;

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={cargando} onRefresh={cargar} tintColor={Colors.verde} />}
    >
      {/* Balance actual */}
      <View style={styles.balanceCard}>
        <Text style={styles.balanceLabel}>Balance acumulado</Text>
        <Text style={styles.balanceMonto}>${Number(usuario?.balance ?? 0).toFixed(2)}</Text>
        <Text style={styles.balanceSub}>El administrador procesará el pago periódicamente</Text>
      </View>

      {/* Historial de liquidaciones */}
      <View style={styles.seccion}>
        <Text style={styles.seccionTitulo}>Historial de pagos</Text>
        {liquidaciones.length === 0 ? (
          <Text style={styles.vacio}>Aún no has recibido pagos.</Text>
        ) : (
          liquidaciones.map(liq => (
            <View key={liq.id} style={styles.liquidacionFila}>
              <View>
                <Text style={styles.liquidacionFecha}>
                  {new Date(liq.created_at).toLocaleDateString('es-EC')}
                </Text>
                <Text style={styles.liquidacionSub}>Pago procesado por admin</Text>
              </View>
              <Text style={styles.liquidacionMonto}>
                ${Number(liq.monto_pagado).toFixed(2)}
              </Text>
            </View>
          ))
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  loader: { flex: 1, marginTop: 40 },
  container: { flex: 1, backgroundColor: Colors.fondo },
  content: { padding: 16, gap: 16, paddingBottom: 40 },
  balanceCard: {
    backgroundColor: Colors.verde,
    borderRadius: 20,
    padding: 28,
    alignItems: 'center',
    gap: 6,
  },
  balanceLabel: { fontSize: 14, color: 'rgba(255,255,255,0.8)' },
  balanceMonto: { fontSize: 48, fontWeight: 'bold', color: Colors.blanco },
  balanceSub: { fontSize: 12, color: 'rgba(255,255,255,0.7)', textAlign: 'center' },
  seccion: {
    backgroundColor: Colors.blanco,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    gap: 12,
  },
  seccionTitulo: { fontSize: 15, fontWeight: '700', color: Colors.negro },
  vacio: { fontSize: 14, color: Colors.grisMedio, textAlign: 'center', paddingVertical: 8 },
  liquidacionFila: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: Colors.grisClaro,
  },
  liquidacionFecha: { fontSize: 14, fontWeight: '600', color: Colors.negro },
  liquidacionSub: { fontSize: 12, color: Colors.grisMedio },
  liquidacionMonto: { fontSize: 16, fontWeight: 'bold', color: Colors.verde },
});