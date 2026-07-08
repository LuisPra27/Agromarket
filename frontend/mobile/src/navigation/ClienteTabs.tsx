import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useCarrito } from '../store/CarritoContext';
import { Colors } from '../constants/colors';
import CatalogoScreen from '../screens/cliente/CatalogoScreen';
import CarritoScreen from '../screens/cliente/CarritoScreen';
import MisPedidosScreen from '../screens/cliente/MisPedidosScreen';
import PerfilScreen from '../screens/cliente/PerfilScreen';

const Tab = createBottomTabNavigator();

export default function ClienteTabs() {
  const { cantidadTotal } = useCarrito();

  return (
    <Tab.Navigator
      screenOptions={{
        tabBarActiveTintColor: Colors.verde,
        tabBarInactiveTintColor: Colors.grisMedio,
        tabBarStyle: { borderTopColor: Colors.grisClaro },
        headerStyle: { backgroundColor: Colors.verde },
        headerTintColor: Colors.blanco,
        headerTitleStyle: { fontWeight: 'bold' },
}}
    >
      <Tab.Screen name="Catálogo" component={CatalogoScreen} />
      <Tab.Screen
        name="Carrito"
        component={CarritoScreen}
        options={{
          tabBarBadge: cantidadTotal > 0 ? cantidadTotal : undefined,
          tabBarBadgeStyle: { backgroundColor: '#16a34a' },
        }}
      />
      <Tab.Screen name="Mis Pedidos" component={MisPedidosScreen} />
      <Tab.Screen name="Perfil" component={PerfilScreen} />
    </Tab.Navigator>
  );
}