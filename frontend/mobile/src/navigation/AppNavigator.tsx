import React, { useEffect, useRef } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { ActivityIndicator, View } from 'react-native';
import { useAuth } from '../store/AuthContext';
import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';
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

  // Listener para navegación desde notificaciones push
  // IMPORTANTE: este hook debe ir SIEMPRE antes de cualquier return condicional,
  // si no, React lanza "Rendered more hooks than during the previous render"
  // en cuanto isLoading pasa de true a false.
  useEffect(() => {
    const handlePushNavigation = (event: { pedidoId: number; tipo?: string }) => {
      const { pedidoId } = event;
      if (pedidoId) {
        navigationRef.current?.navigate('SeguimientoPedido', { pedidoId });
      }
    };

    const unsubscribe = pushNavigationEmitter.addListener(handlePushNavigation);
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
    <NavigationContainer ref={navigationRef}>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        {usuario ? (
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
        ) : (
          <>
            <Stack.Screen name="Login" component={LoginScreen} />
            <Stack.Screen
              name="Register"
              component={RegisterScreen}
              options={{
                headerShown: true,
                title: 'Crear cuenta',
                headerTintColor: Colors.verde,
                headerBackTitle: 'Volver',
              }}
            />
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}