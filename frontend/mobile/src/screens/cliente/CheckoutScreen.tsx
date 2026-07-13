import React, { useState, useEffect} from 'react';
import {
  View, Text, StyleSheet, TouchableOpacity, ScrollView,
  Alert, ActivityIndicator, Image, TextInput, Platform,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import * as WebBrowser from 'expo-web-browser';
import { useCarrito } from '../../store/CarritoContext';
import { useAuth } from '../../store/AuthContext';
import api from '../../services/api';
import { Colors } from '../../constants/colors';
import { useNavigation } from '@react-navigation/native';
import MapaCampus from '../../components/MapaCampus';

type MetodoEntrega = 'retiro' | 'delivery';
type MetodoPago = 'transferencia' | 'payphone';

const PAYPHONE_REDIRECT_URI = 'agromarket://payphone-redirect';

export default function CheckoutScreen() {
  const { items, total, limpiar } = useCarrito();
  const { usuario } = useAuth();
  const navigation = useNavigation<any>();

  const [metodo, setMetodo] = useState<MetodoEntrega>('retiro');
  const [metodoPago, setMetodoPago] = useState<MetodoPago>('transferencia');
  const [puntoEncuentro, setPuntoEncuentro] = useState('');
  const [comprobante, setComprobante] = useState<any>(null);
  const [cargando, setCargando] = useState(false);
  const [cuentas, setCuentas] = useState<Record<string, string>>({});
  const [costoDelivery, setCostoDelivery] = useState<number>(0);
  const [pinX, setPinX] = useState<number | null>(null);
  const [pinY, setPinY] = useState<number | null>(null);

  useEffect(() => {
    api.get('/configuraciones/publicas')
      .then(r => {
        setCuentas(r.data);
        if (r.data.costo_delivery) {
          setCostoDelivery(Number(r.data.costo_delivery));
        }
      })
      .catch(() => {});
  }, []);

  const totalConDelivery = metodo === 'delivery' ? total + costoDelivery : total;

  const seleccionarComprobante = async () => {
    const permiso = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permiso.granted) {
      Alert.alert('Permiso requerido', 'Necesitamos acceso a tu galería para subir el comprobante.');
      return;
    }

    const resultado = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      quality: 0.8,
      allowsEditing: false,
    });

    if (!resultado.canceled && resultado.assets[0]) {
      setComprobante(resultado.assets[0]);
    }
  };

  const tomarFoto = async () => {
    const permiso = await ImagePicker.requestCameraPermissionsAsync();
    if (!permiso.granted) {
      Alert.alert('Permiso requerido', 'Necesitamos acceso a la cámara.');
      return;
    }

    const resultado = await ImagePicker.launchCameraAsync({
      quality: 0.8,
      allowsEditing: false,
    });

    if (!resultado.canceled && resultado.assets[0]) {
      setComprobante(resultado.assets[0]);
    }
  };

  const confirmarPedidoPayphone = async (tipo: 'boton' | 'cajita') => {
    if (metodo === 'delivery' && !puntoEncuentro.trim()) {
      Alert.alert('Falta el punto de encuentro', 'Por favor indica dónde te encontramos.');
      return;
    }

    setCargando(true);
    try {
      // 1. Creamos el pedido (sin comprobante, estado pendiente_pago) y
      //    preparamos la transacción en Payphone.
      const prepareResponse = await api.post('/pedidos/payphone/prepare', {
        metodo_entrega: metodo,
        items: items.map(item => ({
          producto_id: item.producto.id,
          cantidad: item.cantidad,
        })),
        ...(metodo === 'delivery' && {
          punto_encuentro: puntoEncuentro,
          pin_x: pinX,
          pin_y: pinY,
        }),
      });

      const { pedido, bridge_url, bridge_url_cajita } = prepareResponse.data;
      const urlAAbrir = tipo === 'cajita' ? bridge_url_cajita : bridge_url;

      if (!urlAAbrir) {
        throw new Error('No se pudo generar el enlace de pago.');
      }

      // 2. Abrimos el navegador del sistema con nuestra página puente
      //    (necesaria porque Payphone exige que la navegación hacia su
      //    formulario venga de un dominio web real, no de una app), y
      //    esperamos a que rebote de vuelta a la app.
      const resultado = await WebBrowser.openAuthSessionAsync(
        urlAAbrir,
        PAYPHONE_REDIRECT_URI
      );

      if (resultado.type !== 'success' || !resultado.url) {
        // El usuario canceló o cerró el navegador antes de terminar.
        Alert.alert(
          'Pago no completado',
          'No se completó el pago. Puedes intentarlo de nuevo desde "Mis Pedidos".'
        );
        return;
      }

      // 3. La página puente (/payphone/confirmar) ya confirmó el pago
      //    server-to-server con Payphone y aprobó el pedido si correspondía.
      //    Acá solo leemos el resultado que nos rebotó en el deep link.
      const queryString = resultado.url.split('?')[1] ?? '';
      const params = Object.fromEntries(
        queryString.split('&').filter(Boolean).map(pair => {
          const [key, value] = pair.split('=');
          return [decodeURIComponent(key), decodeURIComponent(value ?? '')];
        })
      );

      if (params.resultado !== 'exito') {
        Alert.alert(
          'Pago no aprobado',
          'Payphone no aprobó el pago. Puedes intentarlo de nuevo desde "Mis Pedidos".'
        );
        return;
      }

      const pedidoIdFinal = params.pedido_id || pedido.id;

      limpiar();
      Alert.alert('¡Pago confirmado! 🎉', 'Tu pedido ya está en preparación.', [
        {
          text: 'Ver mi pedido',
          onPress: () =>
            navigation.reset({
              index: 0,
              routes: [{ name: 'SeguimientoPedido', params: { pedidoId: Number(pedidoIdFinal) } }],
            }),
        },
      ]);
    } catch (error: any) {
      const mensaje = error.response?.data?.message || error.message || 'Error al procesar el pago.';
      Alert.alert('Error', mensaje);
    } finally {
      setCargando(false);
    }
  };

  const confirmarPedido = async () => {
    if (!comprobante) {
      Alert.alert('Falta el comprobante', 'Por favor sube la foto de tu transferencia.');
      return;
    }

    if (metodo === 'delivery' && !puntoEncuentro.trim()) {
      Alert.alert('Falta el punto de encuentro', 'Por favor indica dónde te encontramos.');
      return;
    }

    setCargando(true);
    try {
      const formData = new FormData();

      formData.append('metodo_entrega', metodo);
      formData.append('comprobante', {
        uri: comprobante.uri,
        name: `comprobante_${Date.now()}.jpg`,
        type: 'image/jpeg',
      } as any);

      items.forEach((item, index) => {
        formData.append(`items[${index}][producto_id]`, item.producto.id.toString());
        formData.append(`items[${index}][cantidad]`, item.cantidad.toString());
      });

      if (metodo === 'delivery') {
        formData.append('punto_encuentro', puntoEncuentro);
        if (pinX !== null) formData.append('pin_x', pinX.toString());
        if (pinY !== null) formData.append('pin_y', pinY.toString());
      }

      const response = await api.post('/pedidos', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      limpiar();
      Alert.alert(
        '¡Pedido enviado!',
        'Tu pedido está en revisión. Te notificaremos cuando sea aprobado.',
        [
          {
            text: 'Ver mis pedidos',
            onPress: () =>
              navigation.reset({
                index: 0,
                routes: [
                  {
                    name: 'Main',
                    params: { screen: 'Mis Pedidos' },
                  },
                ],
              }),
          },
        ]
      );
    } catch (error: any) {
      const mensaje = error.response?.data?.message || 'Error al crear el pedido.';
      Alert.alert('Error', mensaje);
    } finally {
      setCargando(false);
    }
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>

      {/* Resumen del carrito */}
      <View style={styles.seccion}>
        <Text style={styles.titulo}>Resumen del pedido</Text>
        {items.map(item => (
          <View key={item.producto.id} style={styles.itemRow}>
            <Text style={styles.itemNombre}>
              {item.producto.nombre} × {item.cantidad}
            </Text>
            <Text style={styles.itemPrecio}>
              ${(Number(item.producto.precio) * item.cantidad).toFixed(2)}
            </Text>
          </View>
        ))}
        
        {/* Subtotal */}
        <View style={styles.totalRow}>
          <Text style={styles.totalLabel}>Subtotal:</Text>
          <Text style={styles.totalMonto}>${total.toFixed(2)}</Text>
        </View>

        {/* Costo delivery si aplica */}
        {metodo === 'delivery' && costoDelivery > 0 && (
          <View style={styles.totalRow}>
            <Text style={styles.totalLabel}>Costo delivery:</Text>
            <Text style={styles.totalMonto}>+ $${costoDelivery.toFixed(2)}</Text>
          </View>
        )}

        {/* Total final */}
        <View style={[styles.totalRow, styles.totalFinal]}>
          <Text style={styles.totalLabel}>Total a pagar:</Text>
          <Text style={styles.totalMonto}>${totalConDelivery.toFixed(2)}</Text>
        </View>
      </View>

      {/* Método de entrega */}
      <View style={styles.seccion}>
        <Text style={styles.titulo}>Método de entrega</Text>
        <View style={styles.metodoRow}>
          <TouchableOpacity
            style={[styles.metodoBtn, metodo === 'retiro' && styles.metodoBtnActivo]}
            onPress={() => setMetodo('retiro')}
          >
            <Text style={[styles.metodoBtnTexto, metodo === 'retiro' && styles.metodoBtnTextoActivo]}>
              🏪 Retiro en local
            </Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.metodoBtn, metodo === 'delivery' && styles.metodoBtnActivo]}
            onPress={() => setMetodo('delivery')}
          >
            <Text style={[styles.metodoBtnTexto, metodo === 'delivery' && styles.metodoBtnTextoActivo]}>
              🛵 Delivery ${costoDelivery > 0 ? `+$${costoDelivery.toFixed(2)}` : ''}
            </Text>
          </TouchableOpacity>
        </View>

        {metodo === 'delivery' && (
        <View style={styles.deliveryContainer}>
          <TextInput
            style={styles.input}
            placeholder="Describe tu ubicación (ej: Facultad de Ingeniería, piso 2, aula 104)"
            placeholderTextColor={Colors.grisMedio}
            value={puntoEncuentro}
            onChangeText={setPuntoEncuentro}
            multiline
            numberOfLines={2}
          />
          <MapaCampus
            pinX={pinX}
            pinY={pinY}
            onPinChange={(x, y) => { setPinX(x); setPinY(y); }}
          />
        </View>
      )}
      </View>

      {/* Método de pago */}
      <View style={styles.seccion}>
        <Text style={styles.titulo}>Método de pago</Text>
        <View style={styles.metodoRow}>
          <TouchableOpacity
            style={[styles.metodoBtn, metodoPago === 'transferencia' && styles.metodoBtnActivo]}
            onPress={() => setMetodoPago('transferencia')}
          >
            <Text style={[styles.metodoBtnTexto, metodoPago === 'transferencia' && styles.metodoBtnTextoActivo]}>
              🏦 Transferencia
            </Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.metodoBtn, metodoPago === 'payphone' && styles.metodoBtnActivo]}
            onPress={() => setMetodoPago('payphone')}
          >
            <Text style={[styles.metodoBtnTexto, metodoPago === 'payphone' && styles.metodoBtnTextoActivo]}>
              💳 Payphone
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* Datos para transferencia */}
      {metodoPago === 'transferencia' && (
      <>
      <View style={styles.seccion}>
        <Text style={styles.titulo}>💳 Datos para la transferencia</Text>
        <Text style={styles.subtitulo}>
          Transfiere exactamente <Text style={styles.montoDestacado}>${totalConDelivery.toFixed(2)}</Text> a esta cuenta:
        </Text>

        <View style={styles.cuentaContainer}>
          {[
            { label: 'Banco', valor: cuentas.cuenta_banco },
            { label: 'Tipo de cuenta', valor: cuentas.cuenta_tipo },
            { label: 'Número de cuenta', valor: cuentas.cuenta_numero },
            { label: 'Titular', valor: cuentas.cuenta_titular },
            { label: 'Cédula / RUC', valor: cuentas.cuenta_cedula },
          ].map(({ label, valor }) => (
            <View key={label} style={styles.cuentaFila}>
              <Text style={styles.cuentaLabel}>{label}:</Text>
              <Text style={styles.cuentaValor}>{valor ?? '—'}</Text>
            </View>
          ))}
        </View>

        <TouchableOpacity
          style={styles.btnCopiar}
          onPress={() => {
            // En el futuro: Clipboard.setString(cuentas.cuenta_numero)
            Alert.alert('Número de cuenta', cuentas.cuenta_numero ?? '');
          }}
        >
          <Text style={styles.btnCopiarTexto}>📋 Ver número de cuenta</Text>
        </TouchableOpacity>
      </View>

      {/* Comprobante de pago */}
      <View style={styles.seccion}>
        <Text style={styles.titulo}>Comprobante de transferencia</Text>
        <Text style={styles.subtitulo}>
          Transfiere ${totalConDelivery.toFixed(2)} y sube la captura de pantalla del comprobante.
        </Text>

        {comprobante ? (
          <View style={styles.comprobantePreview}>
            <Image source={{ uri: comprobante.uri }} style={styles.comprobanteImg} resizeMode="cover" />
            <TouchableOpacity
              style={styles.btnCambiar}
              onPress={seleccionarComprobante}
            >
              <Text style={styles.btnCambiarTexto}>Cambiar imagen</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <View style={styles.comprobanteOpciones}>
            <TouchableOpacity style={styles.btnComprobante} onPress={seleccionarComprobante}>
              <Text style={styles.btnComprobanteTexto}>📁 Elegir de galería</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.btnComprobante} onPress={tomarFoto}>
              <Text style={styles.btnComprobanteTexto}>📷 Tomar foto</Text>
            </TouchableOpacity>
          </View>
        )}
      </View>
      </>
      )}

      {/* Pago con Payphone */}
      {metodoPago === 'payphone' && (
      <View style={styles.seccion}>
        <Text style={styles.titulo}>💳 Pago con Payphone</Text>
        <Text style={styles.subtitulo}>
          Vas a pagar <Text style={styles.montoDestacado}>${totalConDelivery.toFixed(2)}</Text> con tarjeta
          a través de Payphone. Elige cómo prefieres pagar:
        </Text>

        <TouchableOpacity
          style={[styles.btnConfirmar, cargando && styles.btnDeshabilitado, { marginTop: 16 }]}
          onPress={() => confirmarPedidoPayphone('boton')}
          disabled={cargando}
        >
          {cargando ? (
            <ActivityIndicator color={Colors.blanco} />
          ) : (
            <Text style={styles.btnConfirmarTexto}>Pagar con Botón Payphone</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.btnSecundario, cargando && styles.btnDeshabilitado]}
          onPress={() => confirmarPedidoPayphone('cajita')}
          disabled={cargando}
        >
          {cargando ? (
            <ActivityIndicator color={Colors.verde} />
          ) : (
            <Text style={styles.btnSecundarioTexto}>Pagar con Cajita de Pagos</Text>
          )}
        </TouchableOpacity>
      </View>
      )}

      {/* Botón confirmar (solo transferencia; Payphone tiene sus propios botones arriba) */}
      {metodoPago === 'transferencia' && (
      <TouchableOpacity
        style={[styles.btnConfirmar, cargando && styles.btnDeshabilitado]}
        onPress={confirmarPedido}
        disabled={cargando}
      >
        {cargando ? (
          <ActivityIndicator color={Colors.blanco} />
        ) : (
          <Text style={styles.btnConfirmarTexto}>Confirmar pedido</Text>
        )}
      </TouchableOpacity>
      )}

    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.fondo },
  content: { padding: 16, gap: 16, paddingBottom: 40 },
  seccion: {
    backgroundColor: Colors.blanco,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    gap: 8,
  },
  titulo: { fontSize: 16, fontWeight: '700', color: Colors.negro, marginBottom: 4 },
  subtitulo: { fontSize: 13, color: Colors.grisMedio },
  itemRow: { flexDirection: 'row', justifyContent: 'space-between' },
  itemNombre: { fontSize: 14, color: Colors.grisOscuro, flex: 1 },
  itemPrecio: { fontSize: 14, fontWeight: '600', color: Colors.negro },
  totalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: Colors.grisClaro,
    paddingTop: 8,
    marginTop: 4,
  },
  totalFinal: {
    borderTopWidth: 2,
    borderTopColor: Colors.negro,
    marginTop: 8,
    paddingTop: 12,
  },
  totalLabel: { fontSize: 15, fontWeight: '600', color: Colors.grisOscuro },
  totalMonto: { fontSize: 18, fontWeight: 'bold', color: Colors.verde },
  metodoRow: { flexDirection: 'row', gap: 12 },
  metodoBtn: {
    flex: 1,
    borderWidth: 2,
    borderColor: Colors.grisClaro,
    borderRadius: 12,
    padding: 12,
    alignItems: 'center',
  },
  metodoBtnActivo: { borderColor: Colors.verde, backgroundColor: '#f0faf0' },
  metodoBtnTexto: { fontSize: 13, color: Colors.grisMedio, fontWeight: '500' },
  metodoBtnTextoActivo: { color: Colors.verde, fontWeight: '700' },
  input: {
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    borderRadius: 12,
    padding: 12,
    fontSize: 14,
    color: Colors.negro,
    textAlignVertical: 'top',
    marginTop: 8,
  },
  comprobanteOpciones: { flexDirection: 'row', gap: 12 },
  btnComprobante: {
    flex: 1,
    borderWidth: 2,
    borderColor: Colors.grisClaro,
    borderRadius: 12,
    padding: 14,
    alignItems: 'center',
    borderStyle: 'dashed',
  },
  btnComprobanteTexto: { fontSize: 13, color: Colors.grisOscuro },
  comprobantePreview: { alignItems: 'center', gap: 8 },
  comprobanteImg: { width: '100%', height: 200, borderRadius: 12 },
  btnCambiar: {
    borderWidth: 1,
    borderColor: Colors.verde,
    borderRadius: 8,
    paddingHorizontal: 16,
    paddingVertical: 8,
  },
  btnCambiarTexto: { color: Colors.verde, fontSize: 13 },
  btnConfirmar: {
    backgroundColor: Colors.verde,
    borderRadius: 14,
    paddingVertical: 18,
    alignItems: 'center',
  },
  btnDeshabilitado: { opacity: 0.6 },
  btnConfirmarTexto: { color: Colors.blanco, fontSize: 16, fontWeight: '700' },
  btnSecundario: {
    borderWidth: 2,
    borderColor: Colors.verde,
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 10,
  },
  btnSecundarioTexto: { color: Colors.verde, fontSize: 15, fontWeight: '700' },

  cuentaContainer: {
  backgroundColor: Colors.fondo,
  borderRadius: 10,
  padding: 12,
  gap: 8,
  },
  cuentaFila: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  cuentaLabel: { fontSize: 13, color: Colors.grisMedio, flex: 1 },
  cuentaValor: { fontSize: 13, fontWeight: '600', color: Colors.negro, flex: 1, textAlign: 'right' },
  montoDestacado: { fontWeight: 'bold', color: Colors.verde },
  btnCopiar: {
    borderWidth: 1,
    borderColor: Colors.verde,
    borderRadius: 10,
    padding: 10,
    alignItems: 'center',
    marginTop: 4,
  },
  btnCopiarTexto: { color: Colors.verde, fontSize: 13, fontWeight: '600' },
  deliveryContainer: { gap: 12 },
});