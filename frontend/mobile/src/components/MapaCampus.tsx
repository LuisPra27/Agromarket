import React, { useState, useRef } from 'react';
import {
  View, Text, StyleSheet, Image, TouchableOpacity,
  Dimensions, Modal,
} from 'react-native';
import { ReactNativeZoomableView } from '@openspacelabs/react-native-zoomable-view';
import { Colors } from '../constants/colors';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

interface Props {
  pinX: number | null;
  pinY: number | null;
  onPinChange: (x: number, y: number) => void;
}

export default function MapaCampus({ pinX, pinY, onPinChange }: Props) {
  const [modalVisible, setModalVisible] = useState(false);
  const [imageSize, setImageSize] = useState({ width: 1, height: 1 });
  const containerRef = useRef<View>(null);

  const handleMapPress = (event: any) => {
    const { locationX, locationY } = event.nativeEvent;
    const xPct = Math.min(100, Math.max(0, (locationX / imageSize.width) * 100));
    const yPct = Math.min(100, Math.max(0, (locationY / imageSize.height) * 100));
    onPinChange(Math.round(xPct * 10) / 10, Math.round(yPct * 10) / 10);
  };

  // Calcula el tamaño real de la imagen manteniendo el ratio
  const mapaWidth = SCREEN_WIDTH;
  const mapaHeight = SCREEN_HEIGHT * 0.75;

  return (
    <View style={styles.container}>
      <Text style={styles.label}>📍 Marca tu ubicación en el mapa</Text>
      <Text style={styles.hint}>Toca el mapa para colocar el pin donde estás</Text>

      {/* Preview pequeño */}
      <TouchableOpacity
        style={styles.mapaPreview}
        onPress={() => setModalVisible(true)}
        activeOpacity={0.9}
      >
        <Image
          source={require('../../assets/mapauleam.png')}
          style={styles.mapaPreviewImg}
          resizeMode="cover"
        />
        {pinX !== null && pinY !== null && (
          <View style={[styles.pin, {
            left: `${pinX}%` as any,
            top: `${pinY}%` as any,
          }]}>
            <Text style={styles.pinEmoji}>📍</Text>
          </View>
        )}
        <View style={styles.previewOverlay}>
          <Text style={styles.previewOverlayTexto}>
            {pinX !== null ? '✏️ Toca para cambiar pin' : '🗺️ Toca para abrir mapa'}
          </Text>
        </View>
      </TouchableOpacity>

      {pinX !== null && pinY !== null && (
        <Text style={styles.pinConfirmado}>
          ✅ Pin colocado correctamente
        </Text>
      )}

      {/* Modal con mapa completo */}
      <Modal
        visible={modalVisible}
        animationType="slide"
        statusBarTranslucent
        onRequestClose={() => setModalVisible(false)}
      >
        <View style={styles.modalContainer}>
          {/* Header */}
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitulo}>Toca donde estás</Text>
            <TouchableOpacity
              style={styles.btnConfirmar}
              onPress={() => setModalVisible(false)}
            >
              <Text style={styles.btnConfirmarTexto}>✓ Confirmar</Text>
            </TouchableOpacity>
          </View>

          <Text style={styles.modalHint}>
            Haz zoom para ver mejor el mapa, luego toca tu ubicación
          </Text>

          {/* Mapa con zoom */}
          <ReactNativeZoomableView
            maxZoom={4}
            minZoom={0.5}
            zoomStep={0.5}
            initialZoom={1}
            bindToBorders
            style={styles.zoomContainer}
          >
            <TouchableOpacity
              activeOpacity={1}
              onPress={handleMapPress}
              onLayout={(e) => {
                setImageSize({
                  width: e.nativeEvent.layout.width,
                  height: e.nativeEvent.layout.height,
                });
              }}
            >
              <Image
                source={require('../../assets/mapauleam.png')}
                style={{ width: mapaWidth, height: mapaHeight }}
                resizeMode="contain"
              />
              {pinX !== null && pinY !== null && (
                <View style={[styles.pinModal, {
                  left: `${pinX}%` as any,
                  top: `${pinY}%` as any,
                }]}>
                  <Text style={styles.pinModalEmoji}>📍</Text>
                </View>
              )}
            </TouchableOpacity>
          </ReactNativeZoomableView>

          {/* Info del pin */}
          <View style={styles.modalFooter}>
            {pinX !== null && pinY !== null ? (
              <Text style={styles.pinInfo}>📍 Pin colocado — toca "Confirmar" para continuar</Text>
            ) : (
              <Text style={styles.pinInfo}>👆 Toca el mapa para colocar el pin</Text>
            )}
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { gap: 8 },
  label: { fontSize: 13, fontWeight: '600', color: Colors.grisOscuro },
  hint: { fontSize: 12, color: Colors.grisMedio },
  mapaPreview: {
    height: 150,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    overflow: 'hidden',
    position: 'relative',
  },
  mapaPreviewImg: { width: '100%', height: '100%' },
  pin: {
    position: 'absolute',
    transform: [{ translateX: -12 }, { translateY: -24 }],
  },
  pinEmoji: { fontSize: 24 },
  previewOverlay: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: 'rgba(0,0,0,0.5)',
    padding: 8,
    alignItems: 'center',
  },
  previewOverlayTexto: { color: Colors.blanco, fontSize: 12, fontWeight: '500' },
  pinConfirmado: { fontSize: 12, color: Colors.verde, fontWeight: '500' },
  modalContainer: { flex: 1, backgroundColor: Colors.negro },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    paddingTop: 48,
    backgroundColor: Colors.verde,
  },
  modalTitulo: { fontSize: 18, fontWeight: '700', color: Colors.blanco },
  btnConfirmar: {
    backgroundColor: 'rgba(255,255,255,0.25)',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
  },
  btnConfirmarTexto: { color: Colors.blanco, fontWeight: '600', fontSize: 14 },
  modalHint: {
    color: 'rgba(255,255,255,0.7)',
    fontSize: 12,
    textAlign: 'center',
    paddingVertical: 8,
    backgroundColor: Colors.verde,
  },
  zoomContainer: { flex: 1 },
  pinModal: {
    position: 'absolute',
    transform: [{ translateX: -12 }, { translateY: -24 }],
  },
  pinModalEmoji: { fontSize: 32 },
  modalFooter: {
    padding: 16,
    backgroundColor: 'rgba(0,0,0,0.8)',
    alignItems: 'center',
  },
  pinInfo: { color: Colors.blanco, fontSize: 13, textAlign: 'center' },
});