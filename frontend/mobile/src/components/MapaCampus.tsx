import React, { useState, useRef } from 'react';
import {
  View, Text, StyleSheet, TouchableOpacity,
  Dimensions, Modal,
} from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { ReactNativeZoomableView } from '@openspacelabs/react-native-zoomable-view';
import { Colors } from '../constants/colors';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

// Relación de aspecto REAL de assets/mapa-campus.png (ancho / alto).
// mapa-campus.png mide 2923x2162px → 1.352. Si este archivo cambia de tamaño,
// hay que actualizar este número (o medirlo en runtime con Image.getSize).
const IMAGE_ASPECT_RATIO = 2923 / 2162;

// La caja del modal y la del preview usan EXACTAMENTE esta proporción,
// así la imagen la llena por completo (sin bandas vacías ni recortes) y el
// mismo % de x/y significa el mismo punto visual en ambos lugares.
const MAP_MODAL_WIDTH = SCREEN_WIDTH;
const MAP_MODAL_HEIGHT = MAP_MODAL_WIDTH / IMAGE_ASPECT_RATIO;

const MAP_ASSET = require('../../assets/mapa-campus.png');

// Activa esto mientras depuras el problema del pin. Te va a imprimir en la
// consola el objeto completo que reporta la librería en cada evento de zoom/pan.
// Pégame esos logs (sobre todo offsetX, offsetY, zoomLevel) y calibro la fórmula
// de handleConfirmar con datos reales en vez de a ciegas.
const DEBUG_TRANSFORM = true;

interface Props {
  pinX: number | null;
  pinY: number | null;
  onPinChange: (x: number, y: number) => void;
}

export default function MapaCampus({ pinX, pinY, onPinChange }: Props) {
  const [modalVisible, setModalVisible] = useState(false);
  const zoomableRef = useRef<any>(null);

  // Guardamos el último estado de transformación (zoom + pan) reportado por la librería.
  // Usamos un ref (no useState) porque estos eventos se disparan muy seguido durante
  // el gesto y no necesitamos re-render en cada uno.
  const transformRef = useRef({ zoomLevel: 1, offsetX: 0, offsetY: 0 });

  // Todos estos callbacks devuelven void según la documentación oficial de la librería
  const handleZoomAfter = (_e: any, _gs: any, zoomableViewEventObject: any): void => {
    if (DEBUG_TRANSFORM) console.log('[MapaCampus] onZoomAfter:', JSON.stringify(zoomableViewEventObject));
    transformRef.current = zoomableViewEventObject;
  };

  // NOTA: deliberadamente NO usamos onPanResponderMove. Ese callback intercepta
  // el gesto en cada frame del arrastre y devolver un valor (true/false) hace que
  // la librería deje de aplicar su panning normal (por eso el mapa solo se movía
  // "en slingshot" al soltar rápido). Como solo necesitamos el offset/zoom en el
  // momento de Confirmar, basta con actualizarlo en onZoomAfter y onPanResponderEnd.
  const handlePanResponderEnd = (_e: any, _gs: any, zoomableViewEventObject: any): void => {
    if (DEBUG_TRANSFORM) console.log('[MapaCampus] onPanResponderEnd:', JSON.stringify(zoomableViewEventObject));
    transformRef.current = zoomableViewEventObject;
  };

  // El pin siempre está fijo en el centro de la pantalla (visualmente).
  // Al confirmar, calculamos qué punto del mapa ORIGINAL quedó bajo ese centro.
  const handleConfirmar = () => {
    const { zoomLevel, offsetX, offsetY } = transformRef.current;

    // offsetX/offsetY están medidos desde el centro de la imagen.
    // Punto de contenido que quedó bajo el centro de la pantalla:
    const contentX = MAP_MODAL_WIDTH / 2 - offsetX;
    const contentY = MAP_MODAL_HEIGHT / 2 - offsetY;

    const xPct = Math.min(100, Math.max(0, (contentX / MAP_MODAL_WIDTH) * 100));
    const yPct = Math.min(100, Math.max(0, (contentY / MAP_MODAL_HEIGHT) * 100));

    if (DEBUG_TRANSFORM) {
      console.log('[MapaCampus] handleConfirmar → zoomLevel:', zoomLevel, 'offsetX:', offsetX, 'offsetY:', offsetY);
      console.log('[MapaCampus] handleConfirmar → xPct:', xPct, 'yPct:', yPct);
    }

    onPinChange(Math.round(xPct * 10) / 10, Math.round(yPct * 10) / 10);
    setModalVisible(false);
  };

  return (
    <View style={styles.container}>
      <Text style={styles.label}>📍 Marca tu ubicación en el mapa</Text>
      <Text style={styles.hint}>Toca el mapa para colocar el pin donde estás</Text>

      <TouchableOpacity
        style={styles.mapaPreview}
        onPress={() => setModalVisible(true)}
        activeOpacity={0.9}
      >
        <View style={styles.mapaWrapper}>
          <ExpoImage
            source={MAP_ASSET}
            style={styles.mapaPreviewImg}
            contentFit="cover"
            cachePolicy="memory-disk"
          />
          {pinX !== null && pinY !== null && (
            <View style={[styles.pin, {
              left: `${pinX}%` as any,
              top: `${pinY}%` as any,
            }]}>
              <Text style={styles.pinEmoji}>📍</Text>
            </View>
          )}
        </View>
        <View style={styles.previewOverlay}>
          <Text style={styles.previewOverlayTexto}>
            {pinX !== null ? '✏️ Toca para cambiar pin' : '🗺️ Toca para abrir mapa'}
          </Text>
        </View>
      </TouchableOpacity>

      {pinX !== null && pinY !== null && (
        <Text style={styles.pinConfirmado}>✅ Pin colocado correctamente</Text>
      )}

      <Modal
        visible={modalVisible}
        animationType="slide"
        statusBarTranslucent
        onRequestClose={() => setModalVisible(false)}
      >
        <View style={styles.modalContainer}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitulo}>Mueve el mapa hasta tu ubicación</Text>
            <TouchableOpacity style={styles.btnConfirmar} onPress={handleConfirmar}>
              <Text style={styles.btnConfirmarTexto}>✓ Confirmar</Text>
            </TouchableOpacity>
          </View>
          <Text style={styles.modalHint}>
            Usa dos dedos para hacer zoom y arrastra el mapa; el pin queda fijo en el centro
          </Text>

          <View style={styles.zoomWrapper}>
            <ReactNativeZoomableView
              ref={zoomableRef}
              maxZoom={4}
              minZoom={1}
              zoomStep={0.5}
              initialZoom={1}
              bindToBorders={true}
              onZoomAfter={handleZoomAfter}
              onPanResponderEnd={handlePanResponderEnd}
              style={styles.zoomableView}
            >
              {/*
                expo-image decodifica y reescala con mucha mejor calidad que el
                <Image> nativo de RN cuando el contenido se transforma con zoom,
                así que ya NO necesitamos el truco de sobre-muestreo manual
                (OVERSAMPLE + wrapper de recorte) que usábamos antes.
              */}
              <ExpoImage
                source={MAP_ASSET}
                style={{ width: MAP_MODAL_WIDTH, height: MAP_MODAL_HEIGHT }}
                contentFit="contain"
                cachePolicy="memory-disk"
              />
            </ReactNativeZoomableView>

            {/* Pin FIJO, fuera del ZoomableView, siempre centrado */}
            <View pointerEvents="none" style={styles.pinFijoContainer}>
              <Text style={styles.pinModalEmoji}>📍</Text>
            </View>
          </View>

          <View style={styles.modalFooter}>
            <Text style={styles.pinInfo}>
              👆 Centra el mapa bajo el pin y toca "Confirmar"
            </Text>
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
    borderRadius: 12,
    borderWidth: 1,
    borderColor: Colors.grisClaro,
    overflow: 'hidden',
  },
  mapaWrapper: { width: '100%', aspectRatio: IMAGE_ASPECT_RATIO, position: 'relative' },
  mapaPreviewImg: { width: '100%', height: '100%' },
  pin: {
    position: 'absolute',
    transform: [{ translateX: -8 }, { translateY: -16 }],
  },
  pinEmoji: { fontSize: 16 },
  previewOverlay: {
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
  modalTitulo: { fontSize: 16, fontWeight: '700', color: Colors.blanco, flex: 1 },
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
  zoomWrapper: { flex: 1, position: 'relative', alignItems: 'center', justifyContent: 'center' },
  zoomableView: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  pinFijoContainer: {
    position: 'absolute',
    top: '50%',
    left: '50%',
    transform: [{ translateX: -9 }, { translateY: -18 }],
  },
  pinModalEmoji: { fontSize: 18 },
  modalFooter: {
    padding: 16,
    backgroundColor: 'rgba(0,0,0,0.8)',
    alignItems: 'center',
  },
  pinInfo: { color: Colors.blanco, fontSize: 13, textAlign: 'center' },
});