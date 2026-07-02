import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useAuth } from '../store/AuthContext';

// Placeholders temporales
import CatalogoScreen from '../screens/cliente/CatalogoScreen';
import MisPedidosScreen from '../screens/cliente/MisPedidosScreen';
import PerfilScreen from '../screens/cliente/PerfilScreen';
import RepartidorTabsScreen from '../screens/repartidor/RepartidorTabsScreen';

const Tab = createBottomTabNavigator();

export default function ClienteTabs() {
  const { usuario } = useAuth();
  const esRepartidorAprobado = usuario?.estado_repartidor === 'aprobado';

  return (
    <Tab.Navigator>
      <Tab.Screen name="Catálogo" component={CatalogoScreen} />
      <Tab.Screen name="Mis Pedidos" component={MisPedidosScreen} />
      {esRepartidorAprobado && (
        <Tab.Screen name="Repartidor" component={RepartidorTabsScreen} />
      )}
      <Tab.Screen name="Perfil" component={PerfilScreen} />
    </Tab.Navigator>
  );
}