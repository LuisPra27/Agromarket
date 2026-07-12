import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
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
      screenOptions={({ route }) => ({
        tabBarActiveTintColor: Colors.verde,
        tabBarInactiveTintColor: Colors.grisMedio,
        tabBarStyle: { borderTopColor: Colors.grisClaro },
        headerStyle: { backgroundColor: Colors.verde },
        headerTintColor: Colors.blanco,
        headerTitleStyle: { fontWeight: 'bold' },
        tabBarIcon: ({ focused, color, size }) => {
                  let iconName: string;
                  if (route.name === 'Catálogo') {
                    iconName = focused ? 'storefront' : 'storefront-outline';
                  } else if (route.name === 'Carrito') {
                    iconName = focused ? 'cart' : 'cart-outline';
                  } else if (route.name === 'Mis Pedidos') {
                    iconName = focused ? 'bag-handle' : 'bag-handle-outline';
                  } else if (route.name === 'Perfil') {
                    iconName = focused ? 'person' : 'person-outline';
                  } else {
                    iconName = 'help-circle-outline';
                  }
                  return <Ionicons name={iconName as any} size={size} color={color} />;
                },
      })}
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