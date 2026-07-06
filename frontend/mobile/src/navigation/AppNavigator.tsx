import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { ActivityIndicator, View } from 'react-native';
import { useAuth } from '../store/AuthContext';
import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';
import ClienteTabs from './ClienteTabs';
import CheckoutScreen from '../screens/cliente/CheckoutScreen';
import SeguimientoPedidoScreen from '../screens/cliente/SeguimientoPedidoScreen';
import { Colors } from '../constants/colors';

const Stack = createNativeStackNavigator();

export default function AppNavigator() {
  const { usuario, isLoading } = useAuth();

  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator size="large" color={Colors.verde} />
      </View>
    );
  }

  return (
    <NavigationContainer>
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