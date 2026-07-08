import React from 'react';
import { TouchableOpacity, Text } from 'react-native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import TableroPedidosScreen from './TableroPedidosScreen';
import ViajeActualScreen from './ViajeActualScreen';
import EscanerQRScreen from './EscanerQRScreen';
import BilleteraScreen from './BilleteraScreen';
import { Colors } from '../../constants/colors';
import { useAuth } from '../../store/AuthContext';

const Tab = createBottomTabNavigator();

export default function RepartidorTabsScreen() {
  const { usuario } = useAuth();

  return (
    <Tab.Navigator
      screenOptions={({ navigation }) => ({
        tabBarActiveTintColor: Colors.naranja,
        tabBarInactiveTintColor: Colors.grisMedio,
        headerShown: true,
        headerStyle: { backgroundColor: Colors.naranja },
        headerTintColor: Colors.blanco,
        headerTitleStyle: { fontWeight: 'bold' },
        headerLeft: () => (
          <TouchableOpacity
            onPress={() => navigation.getParent()?.goBack()}
            style={{ paddingHorizontal: 16 }}
          >
            <Text style={{ color: Colors.blanco, fontSize: 15, fontWeight: '600' }}>‹ Volver</Text>
          </TouchableOpacity>
        ),
      })}
    >
      <Tab.Screen
        name="Viajes"
        component={TableroPedidosScreen}
        options={{ title: 'Viajes disponibles' }}
      />
      <Tab.Screen
        name="ViajeActual"
        component={ViajeActualScreen}
        options={{ title: 'Viaje actual', tabBarLabel: 'En curso' }}
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