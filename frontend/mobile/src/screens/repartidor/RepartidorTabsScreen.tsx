import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import TableroPedidosScreen from './TableroPedidosScreen';
import EscanerQRScreen from './EscanerQRScreen';
import BilleteraScreen from './BilleteraScreen';
import { Colors } from '../../constants/colors';
import { useAuth } from '../../store/AuthContext';
import { Text } from 'react-native';

const Tab = createBottomTabNavigator();

export default function RepartidorTabsScreen() {
  const { usuario } = useAuth();

  return (
    <Tab.Navigator
      screenOptions={{
        tabBarActiveTintColor: Colors.naranja,
        tabBarInactiveTintColor: Colors.grisMedio,
        headerShown: true,
        headerStyle: { backgroundColor: Colors.naranja },
        headerTintColor: Colors.blanco,
        headerTitleStyle: { fontWeight: 'bold' },
      }}
    >
      <Tab.Screen
        name="Viajes"
        component={TableroPedidosScreen}
        options={{ title: 'Viajes disponibles' }}
      />
      <Tab.Screen
        name="Escanear"
        component={EscanerQRScreen}
        options={{ title: 'Escanear QR' }}
      />
      <Tab.Screen
        name="Billetera"
        component={BilleteraScreen}
        options={{
          title: 'Mi billetera',
          tabBarBadge: Number(usuario?.balance ?? 0) > 0
            ? `$${Number(usuario?.balance).toFixed(2)}`
            : undefined,
          tabBarBadgeStyle: { backgroundColor: Colors.verde },
        }}
      />
    </Tab.Navigator>
  );
}