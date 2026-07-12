import React, { useEffect, useRef } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { ActivityIndicator, View } from 'react-native';
import { useAuth } from '../store/AuthContext';
import LoginScreen from '../screens/auth/LoginScreen';
import CompletarPerfilScreen from '../screens/auth/CompletarPerfilScreen';
import ClienteTabs from './ClienteTabs';
import CheckoutScreen from '../screens/cliente/CheckoutScreen';
import SeguimientoPedidoScreen from '../screens/cliente/SeguimientoPedidoScreen';
import RepartidorTabsScreen from '../screens/repartidor/RepartidorTabsScreen';
import { Colors } from '../constants/colors';
import { pushNavigationEmitter } from '../store/AuthContext';

const Stack = createNativeStackNavigator();

export default function AppNavigator() {
  const { usuario, isLoading } = useAuth();
  const navigationRef = useRef<any>(null);
  // Si llega un evento de navegación por push antes de que el navigator
  // esté listo (ej. cold start), lo guardamos aquí y lo procesamos en
  // cuanto NavigationContainer dispare onReady.
  const eventoPendienteRef = useRef<{ pedidoId: number; tipo?: string } | null>(null);

  const navegarAPedido = (event: { pedidoId: number; tipo?: string }) => {
    const { pedidoId } = event;
    if (!pedidoId) return;

    if (navigationRef.current?.isReady?.()) {
      navigationRef.current.navigate('SeguimientoPedido', { pedidoId });
    } else {
      // Navigator no listo todavía: lo dejamos pendiente para cuando lo esté.
      eventoPendienteRef.current = event;
    }
  };

  // Listener para navegación desde notificaciones push
  // IMPORTANTE: este hook debe ir SIEMPRE antes de cualquier return condicional,
  // si no, React lanza "Rendered more hooks than during the previous render"
  // en cuanto isLoading pasa de true a false.
  useEffect(() => {
    const unsubscribe = pushNavigationEmitter.addListener(navegarAPedido);

    // Por si el evento se emitió (ej. cold start) antes de que este listener
    // se registrara, revisamos si quedó algo pendiente en el emisor.
    const ultimoEvento = pushNavigationEmitter.consumirUltimoEvento();
    if (ultimoEvento) {
      navegarAPedido(ultimoEvento);
    }

    return () => unsubscribe();
  }, []);

  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator size="large" color={Colors.verde} />
      </View>
    );
  }

  return (
    <NavigationContainer
      ref={navigationRef}
      onReady={() => {
        // El navigator ya está listo: si había un evento pendiente
        // (llegó antes de tiempo), lo procesamos ahora.
        if (eventoPendienteRef.current) {
          const evento = eventoPendienteRef.current;
          eventoPendienteRef.current = null;
          navegarAPedido(evento);
        }
      }}
    >
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        {usuario && usuario.cedula ? (
          <>
            <Stack.Screen name="Main" component={ClienteTabs} />
            <Stack.Screen
              name="Checkout"
              component={CheckoutScreen}
              options={{ headerShown: true, title: 'Finalizar pedido' }}
            />
            <Stack.Screen
              name="SeguimientoPedido"
              component={SeguimientoPedidoScreen}
              options={{ headerShown: true, title: 'Seguimiento del pedido' }}
            />
            <Stack.Screen
              name="RepartidorTabs"
              component={RepartidorTabsScreen}
            />
          </>
        ) : usuario && !usuario.cedula ? (
          // Logueado con Microsoft por primera vez: falta la cédula antes
          // de poder usar el resto de la app.
          <Stack.Screen name="CompletarPerfil" component={CompletarPerfilScreen} />
        ) : (
          <Stack.Screen name="Login" component={LoginScreen} />
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}