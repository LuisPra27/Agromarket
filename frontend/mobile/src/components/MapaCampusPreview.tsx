import React from 'react';
import { View, StyleSheet } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { ReactNativeZoomableView } from '@openspacelabs/react-native-zoomable-view';
import { Colors } from '../constants/colors';

// Misma relación de aspecto que MapaCampus.tsx — debe coincidir siempre con
// el archivo assets/mapa-campus.png (2923x2162px → 1.352). Si el asset cambia
// de tamaño, actualiza este número en AMBOS archivos.
const IMAGE_ASPECT_RATIO = 2923 / 2162;

const MAP_ASSET = require('../../assets/mapa-campus.png');

interface Props {
  pinX: number;
  pinY: number;
  width: number;
}

/**
 * Vista de SOLO LECTURA del pin marcado por el cliente en el checkout.
 * A diferencia de MapaCampus.tsx (que fija el pin en el centro y mueve el
 * mapa bajo él para SELECCIONAR una ubicación), aquí es al revés: el pin
 * está fijo en su posición real (pinX%, pinY%) DENTRO de la imagen, y es
 * el usuario quien hace zoom/pan libremente sobre el contenido para
 * inspeccionar la zona. No hay edición ni botón de confirmar.
 */
export default function MapaCampusPreview({ pinX, pinY, width }: Props) {
  const height = width / IMAGE_ASPECT_RATIO;

  return (
    <View style={[styles.wrapper, { width, height }]}>
      <ReactNativeZoomableView
        maxZoom={4}
        minZoom={1}
        zoomStep={0.5}
        initialZoom={1}
        bindToBorders={true}
        style={styles.zoomableView}
      >
        <View style={{ width, height }}>
          <ExpoImage
            source={MAP_ASSET}
            style={{ width, height }}
            contentFit="cover"
            cachePolicy="memory-disk"
          />
          <View
            pointerEvents="none"
            style={[styles.pin, {
              left: `${pinX}%` as any,
              top: `${pinY}%` as any,
            }]}
          >
            <View style={styles.pinDot} />
          </View>
        </View>
      </ReactNativeZoomableView>
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    borderRadius: 12,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: Colors.grisClaro,
  },
  zoomableView: { flex: 1 },
  pin: {
    position: 'absolute',
    transform: [{ translateX: -12 }, { translateY: -24 }],
    alignItems: 'center',
  },
  pinDot: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: Colors.naranja,
    borderWidth: 3,
    borderColor: Colors.blanco,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 3,
    elevation: 4,
  },
});