import React from 'react';
import { TouchableOpacity, Text } from 'react-native';
import { createBottomTabNavigator, BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import TableroPedidosScreen from './TableroPedidosScreen';
import ViajeActualScreen from './ViajeActualScreen';
import EscanerQRScreen from './EscanerQRScreen';
import BilleteraScreen from './BilleteraScreen';
import { Colors } from '../../constants/colors';
import { useAuth } from '../../store/AuthContext';

const Tab = createBottomTabNavigator();

export default function RepartidorTabsScreen({ navigation }: BottomTabScreenProps<any>) {
  const { usuario } = useAuth();

  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        tabBarActiveTintColor: Colors.naranja,
        tabBarInactiveTintColor: Colors.grisMedio,
        headerShown: true,
        headerStyle: { backgroundColor: Colors.naranja },
        headerTintColor: Colors.blanco,
        headerTitleStyle: { fontWeight: 'bold' },
        headerLeft: () => (
          <TouchableOpacity
            onPress={() => navigation.goBack()}
            style={{ paddingHorizontal: 16 }}
          >
            <Text style={{ color: Colors.blanco, fontSize: 15, fontWeight: '600' }}>‹ Volver</Text>
          </TouchableOpacity>
        ),
        tabBarIcon: ({ focused, color, size }) => {
          let iconName: string;
          if (route.name === 'Viajes') {
            iconName = focused ? 'list' : 'list-outline';
          } else if (route.name === 'ViajeActual') {
            iconName = focused ? 'map' : 'map-outline';
          } else if (route.name === 'Escanear') {
            iconName = focused ? 'qr-code' : 'qr-code-outline';
          } else if (route.name === 'Billetera') {
            iconName = focused ? 'wallet' : 'wallet-outline';
          } else {
            iconName = 'help-circle-outline';
          }
          return <Ionicons name={iconName as any} size={size} color={color} />;
        },
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